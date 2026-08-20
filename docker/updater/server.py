import datetime
import json
import os
import re
import subprocess
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.error import HTTPError, URLError
from urllib.parse import quote
from urllib.request import Request, urlopen

TOKEN = os.environ["UPDATE_AGENT_TOKEN"]
DEFAULT_REF = os.environ.get("UPDATE_REF", "latest").strip()
REPOSITORY = os.environ.get("UPDATE_REPOSITORY", "visezion/EcclesiaOS-Central-Support").strip()
CHANNEL = os.environ.get("UPDATE_CHANNEL", "stable").strip().lower()
REQUIRE_IMMUTABLE = os.environ.get("UPDATE_REQUIRE_IMMUTABLE", "true").lower() == "true"
GITHUB_API_URL = os.environ.get("UPDATE_GITHUB_API_URL", "https://api.github.com").rstrip("/")
GITHUB_TOKEN = os.environ.get("GITHUB_UPDATE_TOKEN", "").strip()
STATUS_PATH = "/shared/framework/update-status.json"
LOCK = threading.Lock()
SEMVER_TAG = re.compile(r"^v\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$")


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


def github_json(path):
    headers = {
        "Accept": "application/vnd.github+json",
        "X-GitHub-Api-Version": "2022-11-28",
        "User-Agent": "EcclesiaOS-Central-Support-Updater",
    }
    if GITHUB_TOKEN:
        headers["Authorization"] = f"Bearer {GITHUB_TOKEN}"
    request = Request(f"{GITHUB_API_URL}{path}", headers=headers)
    with urlopen(request, timeout=15) as response:
        return json.loads(response.read().decode("utf-8"))


def resolve_release(requested):
    requested = (requested or DEFAULT_REF).strip()
    if requested in ("latest", "stable"):
        endpoint = f"/repos/{REPOSITORY}/releases/latest"
    elif SEMVER_TAG.fullmatch(requested):
        endpoint = f"/repos/{REPOSITORY}/releases/tags/{quote(requested, safe='')}"
    else:
        raise ValueError("Updates must use latest or an explicit semantic version tag such as v1.0.1.")

    release = github_json(endpoint)
    if not isinstance(release, dict):
        raise ValueError("GitHub did not return a valid release.")
    if release.get("draft") or (CHANNEL == "stable" and release.get("prerelease")):
        raise ValueError("The selected GitHub release is not a stable published release.")
    if REQUIRE_IMMUTABLE and release.get("immutable") is not True:
        raise ValueError("The selected GitHub release is not immutable.")

    tag = str(release.get("tag_name", "")).strip()
    if not SEMVER_TAG.fullmatch(tag):
        raise ValueError("GitHub returned a release without a supported semantic version tag.")

    return tag, release


def update(ref):
    try:
        write_status("running", "Checking GitHub for the latest stable release.", requested_ref=ref)
        tag, release = resolve_release(ref)
        write_status("running", f"Fetching immutable release {tag}.", ref=tag, release_url=release.get("html_url"))
        run(["git", "fetch", "--force", "--tags", "origin", f"refs/tags/{tag}:refs/tags/{tag}"])
        commit = run(["git", "rev-list", "-n", "1", f"{tag}^{{}}"]).stdout.strip()
        run(["git", "checkout", "--detach", commit])
        write_status("running", "Building and restarting the updated application.", ref=tag, commit=commit[:12])
        compose = ["docker", "compose", "-f", "/workspace/docker-compose.yml", "--env-file", "/workspace/.env"]
        run(compose + ["up", "-d", "--build", "app", "worker", "updater"])
        write_status("running", "Applying database migrations and refreshing caches.", ref=tag, commit=commit[:12])
        run(compose + ["exec", "-T", "app", "php", "artisan", "migrate", "--force"])
        run(compose + ["exec", "-T", "app", "php", "artisan", "optimize"])
        write_status("success", f"Release {tag} installed successfully.", ref=tag, commit=commit[:12], release_url=release.get("html_url"))
    except (subprocess.CalledProcessError, HTTPError, URLError, ValueError, json.JSONDecodeError) as error:
        detail = getattr(error, "stderr", None) or getattr(error, "reason", None) or str(error)
        write_status("failed", str(detail).strip()[-1000:])
    finally:
        LOCK.release()


class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path != "/health":
            self.send_error(404)
            return
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(b'{"status":"ok","service":"update-agent"}')

    def do_POST(self):
        if self.path != "/update" or self.headers.get("Authorization") != f"Bearer {TOKEN}":
            self.send_error(404)
            return
        if not LOCK.acquire(blocking=False):
            self.send_response(409)
            self.end_headers()
            self.wfile.write(b'{"message":"An update is already running."}')
            return
        try:
            length = int(self.headers.get("Content-Length", "0"))
            body = json.loads(self.rfile.read(length) or b"{}")
            ref = str(body.get("ref", DEFAULT_REF)).strip()
            if ref != DEFAULT_REF:
                LOCK.release()
                self.send_error(400, "The updater only accepts its configured release channel.")
                return
        except (ValueError, json.JSONDecodeError):
            LOCK.release()
            self.send_error(400, "Invalid update request.")
            return
        write_status("queued", "The release update has been queued.", requested_ref=ref)
        threading.Thread(target=update, args=(ref,), daemon=True).start()
        self.send_response(202)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(b'{"message":"Release update started. This page will refresh its status automatically."}')

    def log_message(self, format, *args):
        return


write_status("idle", "Ready to install the latest immutable GitHub release.")
ThreadingHTTPServer(("0.0.0.0", 8080), Handler).serve_forever()
