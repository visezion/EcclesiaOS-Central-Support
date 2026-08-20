param(
    [string]$Ref = "main"
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

Write-Host "Fetching the pinned $Ref release..."
git fetch --tags origin $Ref
git checkout --detach FETCH_HEAD

Write-Host "Building and starting the updated services..."
docker compose up -d --build app worker

Write-Host "Applying database migrations and refreshing Laravel caches..."
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize

Write-Host "Update complete. Current commit:"
git rev-parse --short HEAD
docker compose ps
