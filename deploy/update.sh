#!/usr/bin/env sh
set -eu

ref="${1:-main}"
project_root="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$project_root"

if [ ! -f .env ]; then
    echo "Missing .env. Copy .env.docker.example to .env, set secrets, and run this script again." >&2
    exit 1
fi

command -v docker >/dev/null 2>&1 || {
    echo "Docker is not installed or is not available on PATH." >&2
    exit 1
}

echo "Fetching the pinned $ref release..."
git fetch --tags origin "$ref"
git checkout --detach FETCH_HEAD

echo "Building and starting the updated services..."
docker compose up -d --build app worker updater

echo "Applying database migrations and refreshing Laravel caches..."
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize

echo "Update complete. Current commit:"
git rev-parse --short HEAD
docker compose ps
