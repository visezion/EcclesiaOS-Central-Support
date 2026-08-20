import datetime
import json
import os
import subprocess
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

TOKEN = os.environ["UPDATE_AGENT_TOKEN"]
DEFAULT_BRANCH = os.environ.get("UPDATE_BRANCH", "main")
STATUS_PATH = "/shared/framework/update-status.json"
LOCK = threading.Lock()


def write_status(state, message, **extra):
    os.makedirs(os.path.dirname(STATUS_PATH), exist_ok=True)
    status = {
        "state": state,
        "message": message,
        "updated_at": datetime.datetime.now(datetime.timezone.utc).isoformat(),
        **extra,
    }
    temporary = STATUS_PATH + ".tmp"
    with open(temporary, "w", encoding="utf-8") as handle:
        json.dump(status, handle)
    os.replace(temporary, STATUS_PATH)


def run(command):
    return subprocess.run(command, cwd="/workspace", text=True, capture_output=True, check=True)


def update(branch):
    try:
        write_status("running", f"Pulling the latest {branch} release from GitHub.", branch=branch)
        run(["git", "fetch", "origin", branch])
        run(["git", "checkout", branch])
        run(["git", "pull", "--ff-only", "origin", branch])
        commit = run(["git", "rev-parse", "--short", "HEAD"]).stdout.strip()
        write_status("running", "Building and restarting the updated application.", branch=branch, commit=commit)
        compose = ["docker", "compose", "-f", "/workspace/docker-compose.yml", "--env-file", "/workspace/.env"]
        run(compose + ["up", "-d", "--build", "app", "worker"])
        write_status("running", "Applying database migrations and refreshing caches.", branch=branch, commit=commit)
        run(compose + ["exec", "-T", "app", "php", "artisan", "migrate", "--force"])
        run(compose + ["exec", "-T", "app", "php", "artisan", "optimize"])
        write_status("success", "Update completed successfully.", branch=branch, commit=commit)
    except subprocess.CalledProcessError as error:
        detail = (error.stderr or error.stdout or "The update command failed.").strip()[-1000:]
        write_status("failed", detail)
    finally:
        LOCK.release()


class Handler(BaseHTTPRequestHandler):
    def do_POST(self):
        if self.path != "/update" or self.headers.get("Authorization") != f"Bearer {TOKEN}":
            self.send_error(404)
            return
        if not LOCK.acquire(blocking=False):
            self.send_response(409)
            self.end_headers()
            self.wfile.write(b'{"message":"An update is already running."}')
            return
        length = int(self.headers.get("Content-Length", "0"))
        body = json.loads(self.rfile.read(length) or b"{}")
        branch = body.get("branch", DEFAULT_BRANCH)
        if branch != DEFAULT_BRANCH:
            LOCK.release()
            self.send_error(400)
            return
        write_status("queued", "The update has been queued.", branch=branch)
        threading.Thread(target=update, args=(branch,), daemon=True).start()
        self.send_response(202)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(b'{"message":"Update started. This page will refresh its status automatically."}')

    def log_message(self, format, *args):
        return


write_status("idle", "Ready to check GitHub for updates.")
ThreadingHTTPServer(("0.0.0.0", 8080), Handler).serve_forever()
