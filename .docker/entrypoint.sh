#!/usr/bin/env bash
set -euo pipefail

# Runs before every process this image starts (web, queue worker, or an ad-hoc
# artisan command), so it has to be idempotent and cheap.

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "entrypoint: APP_KEY is not set. Generate one with 'php artisan key:generate --show' and pass it in." >&2
    exit 1
fi

# /var/www/html/storage is normally a volume, which starts out empty and hides
# the tree baked into the image. Rebuild what the framework expects.
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/database \
    bootstrap/cache

DB_PATH="${DB_DATABASE:-/var/www/html/storage/database/database.sqlite}"

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ "${DB_PATH}" != ":memory:" ]; then
    touch "${DB_PATH}"
    chown www-data:www-data "${DB_PATH}"
fi

chown -R www-data:www-data storage bootstrap/cache

# Everything below runs as www-data, not root. SQLite in particular writes -wal
# and -shm siblings next to the database, and the Octane workers cannot use
# them if a root process got there first.
run_as_app() {
    gosu www-data "$@"
}

# Warm the caches here rather than at build time: their contents depend on the
# environment variables this container was started with.
run_as_app php artisan config:cache
run_as_app php artisan route:cache
run_as_app php artisan view:cache
run_as_app php artisan storage:link --quiet || true

# Only the web container should migrate; the workers leave RUN_MIGRATIONS unset
# so they do not race it.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    run_as_app php artisan migrate --force --isolated \
        || run_as_app php artisan migrate --force
fi

# Octane is the only long-running process in this image and it has no reason to
# start as root: Swoole binds 8000, which needs no privilege.
exec gosu www-data "$@"
