# EcclesiaOS Central Support

Standalone backend for the central support services used by EcclesiaOS
installations. It is intentionally isolated from the church application so it
can be deployed, secured, backed up, and scaled independently.

## Current API

All routes are under `/api/v1` and require:

- `Authorization: Bearer <installation-token>`
- `X-EcclesiaOS-Installation: <installation-id>`
- `X-EcclesiaOS-Version: <version>`

Implemented client-compatible routes:

- `GET /installations/ping`
- `POST /church/events` with idempotent `event_id` handling
- `GET|POST /community/questions`
- `GET /knowledge/articles`
- `GET /live-support`
- `POST /live-support/messages`

The API currently provides the safe connection and ingestion foundation. Agent
workspace, article management, ticket queues, and remote-support exchange
should be added behind authenticated staff routes before production launch.

## Local setup

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan support:installation-token installation-id "Example Church"
php artisan serve --host=127.0.0.1 --port=8090
```

The token command displays the token once. Store it in the EcclesiaOS
Administration > Support Center > Central Connection settings. Never commit
tokens or `.env` files.

## Production requirements

- Deploy behind HTTPS and a reverse proxy.
- Use a managed MySQL/PostgreSQL database instead of local SQLite.
- Set `APP_DEBUG=false` and a strong `APP_KEY`.
- Store `CENTRAL_SUPPORT_AGENT_TOKEN` in a secret manager.
- Restrict staff/admin routes by separate authentication and permissions before
  exposing an agent dashboard.
- Add rate limiting, queue workers, backups, monitoring, and audit retention.
