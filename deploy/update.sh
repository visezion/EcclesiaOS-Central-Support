#!/usr/bin/env sh
set -eu

branch="${1:-main}"
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

echo "Pulling the latest $branch code..."
git fetch origin "$branch"
git checkout "$branch"
git pull --ff-only origin "$branch"

echo "Building and starting the updated services..."
docker compose up -d --build

echo "Applying database migrations and refreshing Laravel caches..."
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize

echo "Update complete. Current commit:"
git rev-parse --short HEAD
docker compose ps
