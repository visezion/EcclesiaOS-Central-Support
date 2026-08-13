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

The authenticated staff interface provides installation registration and token
rotation, ticket creation/editing/replies/status management, community
moderation, knowledge-base publishing, live-support monitoring, and approved
temporary remote-support grant exchange.

## Local setup

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan support:create-admin "Support Admin" admin@example.com "use-a-strong-password"
php artisan support:installation-token installation-id "Example Church" https://church.example.org
php artisan serve --host=127.0.0.1 --port=8090
```

Open `http://127.0.0.1:8090/login` and sign in with the staff account.

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
- Enforce HTTPS callback URLs in production and allow only trusted church
  domains or a private network egress policy.
- Keep remote-support grants short-lived, single-use, audited, and explicitly
  approved by a church administrator.
- Add rate limiting, queue workers, backups, monitoring, and audit retention.
