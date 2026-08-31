#!/usr/bin/env bash
set -euo pipefail

cd /app

# Work out who to run as from the owner of the bind mount. Under rootless
# Docker that is root (the host user is already mapped onto it); under rootful
# Docker it is the host's own UID, which needs a matching user in the container
# so files written into the mount stay editable on the host.
MOUNT_UID="$(stat -c %u /app)"
MOUNT_GID="$(stat -c %g /app)"

if [ "${MOUNT_UID}" = "0" ]; then
    RUN_AS=root
else
    if ! getent passwd "${MOUNT_UID}" >/dev/null; then
        getent group "${MOUNT_GID}" >/dev/null || groupadd --gid "${MOUNT_GID}" app
        useradd --no-log-init --uid "${MOUNT_UID}" --gid "${MOUNT_GID}" \
                --home-dir /home/app --shell /bin/bash app
    fi
    RUN_AS="$(getent passwd "${MOUNT_UID}" | cut -d: -f1)"
fi

chown -R "${MOUNT_UID}:${MOUNT_GID}" /home/app

# vendor/ and node_modules/ are named volumes rather than part of the bind
# mount, which keeps host-built artefacts out of the container. Docker creates
# them root-owned, so hand them over before installing.
chown "${MOUNT_UID}:${MOUNT_GID}" vendor node_modules

as_user() {
    gosu "${RUN_AS}" "$@"
}

if [ ! -f .env ]; then
    cp .env.example .env
    echo "entrypoint: created .env from .env.example"
fi

if ! grep -qE '^APP_KEY=.+' .env; then
    as_user php artisan key:generate --ansi
fi

if [ ! -f vendor/autoload.php ]; then
    echo "entrypoint: installing composer dependencies (first run, this takes a few minutes)..."
    as_user composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules/vite ]; then
    echo "entrypoint: installing npm dependencies (first run, this takes a few minutes)..."
    as_user npm ci --no-audit --no-fund
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite
chown -R "${MOUNT_UID}:${MOUNT_GID}" storage bootstrap/cache database/database.sqlite

exec gosu "${RUN_AS}" "$@"
