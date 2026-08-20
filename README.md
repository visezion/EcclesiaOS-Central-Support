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
An operational audit log records API events, moderation, ticket changes,
installation actions, and remote-support activity.

Ticket creation and tracking events preserve the original ticket content, and
ticket replies are accepted in both the current nested client format and the
flat compatibility format.

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

## Docker deployment

Docker Compose runs the application with PHP/Apache and a persistent MySQL
database. Docker volumes keep the database and uploaded Laravel storage when a
new application image is built.

On the server:

```bash
cp .env.docker.example .env
# Edit .env and set APP_KEY, APP_URL, DB_PASSWORD, DB_ROOT_PASSWORD,
# CENTRAL_SUPPORT_AGENT_TOKEN, UPDATE_AGENT_TOKEN, and any mail settings.
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

The migration also installs the default published knowledge-base article
“Updating EcclesiaOS safely with Central Support”; no manual seeding step is
required on a new server.

Generate an application key before the first deployment if needed:

```bash
docker compose run --rm app php artisan key:generate --show
```

Copy the printed value into `APP_KEY` in `.env`, then start the services.
Expose the configured port (`8090` by default) through a reverse proxy with
HTTPS. Do not expose MySQL directly to the internet.

### Updating a running deployment

Publish an immutable release tag, then update the server from that exact ref:

```bash
sh deploy/update.sh v1.0.0
```

On Windows PowerShell, use:

```powershell
.\deploy\update.ps1 -Ref v1.0.0
```

The update workflow fetches the exact Git ref/tag without a mutable `git pull`,
checks it out detached, rebuilds the application and worker images, runs
migrations, refreshes Laravel caches, and shows the resulting service status.
It does not remove Docker volumes. Set `UPDATE_REF` in `.env` for the GUI
Update Center; production should use a signed tag rather than `main`.

The **Update Center** page includes an **Update from GitHub** button. It is
available to signed-in staff and uses the internal updater service to perform the same
fast-forward pull, image rebuild, migration, and cache refresh. Set a long,
random `UPDATE_AGENT_TOKEN` in `.env`; the updater is only exposed on the
internal Docker network and the token is never shown in the browser.

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
