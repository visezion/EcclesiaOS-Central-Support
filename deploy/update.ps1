param(
    [string]$Branch = "main"
)

$ErrorActionPreference = "Stop"
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

if (-not (Test-Path ".env")) {
    throw "Missing .env. Copy .env.docker.example to .env, set secrets, and run this script again."
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw "Docker is not installed or is not available on PATH."
}

Write-Host "Pulling the latest $Branch code..."
git fetch origin $Branch
git checkout $Branch
git pull --ff-only origin $Branch

Write-Host "Building and starting the updated services..."
docker compose up -d --build

Write-Host "Applying database migrations and refreshing Laravel caches..."
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize

Write-Host "Update complete. Current commit:"
git rev-parse --short HEAD
docker compose ps
