#!/bin/sh
set -e

# ─────────────────────────────────────────────────────────────────────────────
# Helper: wait for a TCP host:port to accept connections
# ─────────────────────────────────────────────────────────────────────────────
wait_for() {
    local host="$1" port="$2" label="$3"
    echo "[entrypoint] Waiting for ${label} (${host}:${port})..."
    until php -r "
        \$conn = @fsockopen('${host}', ${port}, \$err, \$msg, 2);
        if (\$conn) { fclose(\$conn); exit(0); }
        exit(1);
    " 2>/dev/null; do
        sleep 2
    done
    echo "[entrypoint] ${label} is ready."
}

# ─────────────────────────────────────────────────────────────────────────────
# Helper: wait for an HTTP endpoint to return 200
# ─────────────────────────────────────────────────────────────────────────────
wait_for_http() {
    local url="$1" label="$2"
    echo "[entrypoint] Waiting for ${label} (${url})..."
    until curl -sf "${url}" > /dev/null 2>&1; do
        sleep 2
    done
    echo "[entrypoint] ${label} is ready."
}

# ─────────────────────────────────────────────────────────────────────────────
# Wait for all dependencies
# ─────────────────────────────────────────────────────────────────────────────
wait_for     "${DB_HOST:-postgres}"          "${DB_PORT:-5432}"  "PostgreSQL"
wait_for_http "http://meilisearch:7700/health"                   "MeiliSearch"
wait_for     "minio"                         "9000"              "MinIO"

# ─────────────────────────────────────────────────────────────────────────────
# Bootstrap
# ─────────────────────────────────────────────────────────────────────────────
echo "[entrypoint] Running migrations..."
php artisan migrate --force

# Only seed when the database is empty (idempotency guard).
# We use the roles table as the sentinel — it's populated by both
# DatabaseSeeder and PermissionSeeder and is always present after a full seed.
ROLE_COUNT=$(php artisan tinker --execute="echo \DB::table('roles')->count();" 2>/dev/null | tail -1 | tr -d '[:space:]')

if [ "${ROLE_COUNT}" = "0" ] || [ -z "${ROLE_COUNT}" ]; then
    echo "[entrypoint] Fresh database — running seeders..."
    php artisan db:seed --force
    php artisan db:seed --class=PermissionSeeder --force

    echo "[entrypoint] Syncing Meilisearch index settings..."
    php artisan scout:sync-index-settings

    echo "[entrypoint] Syncing dynamic property attribute filters to Meilisearch..."
    php artisan scout:sync-property-filters

    echo "[entrypoint] Importing properties into Meilisearch (this may take a moment)..."
    php artisan scout:import "App\\Models\\Property"
else
    echo "[entrypoint] Database already seeded (roles: ${ROLE_COUNT}) — skipping seed."

    echo "[entrypoint] Syncing Meilisearch index settings..."
    php artisan scout:sync-index-settings

    echo "[entrypoint] Syncing dynamic property attribute filters to Meilisearch..."
    php artisan scout:sync-property-filters
fi

echo "[entrypoint] Caching config / routes / events..."
php artisan config:cache
php artisan route:cache
php artisan event:cache

echo "[entrypoint] Linking storage..."
php artisan storage:link --force 2>/dev/null || true

# ─────────────────────────────────────────────────────────────────────────────
# Hand off to supervisord (manages Octane + queue workers)
# ─────────────────────────────────────────────────────────────────────────────
echo "[entrypoint] Starting supervisord..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
