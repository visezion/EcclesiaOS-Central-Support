<?php

namespace Database\Seeders;

use App\Models\KnowledgeArticle;
use Illuminate\Database\Seeder;

final class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        KnowledgeArticle::query()->updateOrCreate(
            ['slug' => 'updating-ecclesiaos-safely-with-central-support'],
            [
                'title' => 'Updating EcclesiaOS safely with Central Support',
                'category' => 'Deployments & Updates',
                'published' => true,
                'body' => <<<'ARTICLE'
Purpose
-------
This article is the operating procedure for updating a connected EcclesiaOS installation while keeping Central Support available. EcclesiaOS uses immutable GitHub Releases and a managed release layout. It does not run git pull in the live application, overwrite the church database, replace the .env file, or delete uploaded files.

How the two systems work together
----------------------------------
Central Support stores the church installation record, callback URL, API token, support tickets, community content, knowledge articles, and audit history. EcclesiaOS remains the system that owns the church database, uploaded files, application release, scheduler, and its own update installer.

Use Central Support for support communication and verification. Use the EcclesiaOS Administration > System Updates page, or the approved server-side updater, to install an EcclesiaOS release. Do not try to update an EcclesiaOS installation by rebuilding the Central Support Docker image.

Before publishing an EcclesiaOS release
---------------------------------------
1. Add only forward-compatible database migrations. Never use migrate:fresh, migrate:refresh, db:wipe, or production seeders.
2. Update the VERSION file, for example from 1.0.0 to 2.1.0.
3. Update release/minimum-version.txt with the oldest version that may upgrade directly.
4. Update CHANGELOG.md and test the upgrade against a copy of the previous production database.
5. Merge the release commit into main and create a signed semantic-version tag:

   git tag -s v2.1.0 -m "EcclesiaOS v2.1.0"
   git push origin v2.1.0

6. Publish the GitHub Release with both required assets:
   - ecclesiaos-v2.1.0.zip
   - update-manifest.json

The manifest must identify the version, minimum supported version, minimum PHP version, artifact name, and SHA-256 digest. Production releases should be immutable. The EcclesiaOS updater rejects draft releases, stable prereleases, mutable releases, missing manifests, invalid versions, and digest mismatches.

First deployment on a new server
---------------------------------
1. Use the supported EcclesiaOS Docker setup or the managed Linux layout documented in the EcclesiaOS repository.
2. Keep the application release, shared .env, storage, backups, and database data separate. In the managed layout, the web root is /var/www/ecclesiaos/current/public and shared data is under /var/www/ecclesiaos/shared.
3. Configure the updater with UPDATER_ENABLED=true, UPDATER_INSTALL_ENABLED=true, UPDATE_REPOSITORY=visezion/EcclesiaOS, UPDATE_CHANNEL=stable, UPDATE_REQUIRE_IMMUTABLE=true, and the managed release/shared paths.
4. Configure a private read-only GITHUB_UPDATE_TOKEN only when the repository is private.
5. Run migrations for the first deployment, create the support administrator, and register the installation in Central Support.
6. In Central Support, open Support > Central Connection, register the installation, and securely copy the generated installation token into the EcclesiaOS Central Support settings. Use the exact HTTPS callback URL of this Central Support server.
7. Confirm that /up is healthy, the API ping succeeds, the installation appears enabled in Central Support, and the callback URL has no credentials, query parameters, or fragments.

Normal update procedure
-----------------------
1. Take a database backup before the update. Docker deployments should use the EcclesiaOS backup profile; managed Linux deployments should use the configured mysqldump or pg_dump path.
2. In EcclesiaOS, open Administration > System Updates and check GitHub for a release. The application normally checks approximately every 15 minutes.
3. Review the release version, changelog, minimum version, minimum PHP version, release immutability, manifest, and installation diagnostics.
4. A Super Administrator approves the update by confirming their current password and typing the requested version. This queues the update; the browser request does not perform the installation.
5. The scheduler processes the approved update outside the browser request. It downloads the release asset over the GitHub API, verifies the size and SHA-256 digest, extracts it into a new release directory, links the shared .env and storage, runs safe migrations and optimizations, switches the current release, and checks the health URL.
6. Keep the previous release directory and the pre-update backup until the new version has been verified.
7. Return to Central Support and verify the installation's last-seen time and version. Send a test support event or open the Central Connection health check if the version does not update.

Docker commands for an EcclesiaOS release
------------------------------------------
The exact commands depend on the release version and the server's .env.docker file. The supported pattern is:

   sh docker/setup.sh
   sh docker/update.sh 2.1.0

The update script creates a database backup, pulls the pinned EcclesiaOS app/web/queue/scheduler images for the selected version, updates ECCLESIAOS_VERSION, recreates changed services without removing persistent volumes, and runs a post-update health/about check. Never run docker compose down -v during an update; it can remove the database or application-managed volumes.

Central Support deployment updates
-----------------------------------
Central Support is a separate Laravel service. Update it only from this repository and keep its database/storage volumes. Use an explicit Git ref or immutable tag, then rebuild the app and worker containers:

   sh deploy/update.sh v1.0.0

The script fetches the requested ref, checks it out without a mutable pull, rebuilds the application, runs php artisan migrate --force, refreshes Laravel caches, and leaves Docker volumes untouched. Set UPDATE_REF to the release tag used by your deployment. If the server is intentionally tracking main, understand that main is mutable and is less suitable for production than a signed release tag.

After updating Central Support, verify /up, /login, /api/v1/installations/ping, the Update Center, the Knowledge Base, and one authenticated callback from EcclesiaOS. Do not expose the MySQL port publicly. Keep UPDATE_AGENT_TOKEN and CENTRAL_SUPPORT_AGENT_TOKEN secret.

Rollback and failure handling
-----------------------------
If an update fails, do not delete the previous release or volumes. Inspect the EcclesiaOS scheduler/application logs, confirm the backup exists, and use the documented app:update-rollback command only after checking the release status. A code rollback does not reverse database migrations, so migrations must remain backward compatible.

If Central Support fails after an update, inspect docker compose logs app worker updater, confirm the database is healthy, and retry the same immutable ref after correcting the cause. If the Central Support updater reports a dirty working tree or ref error, resolve the server checkout state rather than forcing a reset over local changes.

Security checklist
------------------
- Use HTTPS for staff pages, API calls, and callbacks.
- Use separate long random secrets for the Central Support agent token and updater token.
- Restrict staff and Super Administrator accounts to named operators with strong passwords.
- Keep GitHub tokens read-only and scoped to the required repository.
- Back up the database before every release and test restoration regularly.
- Never commit .env files, tokens, database files, storage uploads, or production caches.
- Review the Central Support audit log after registration, token rotation, remote support, and update-related actions.
ARTICLE,
            ],
        );
    }
}
