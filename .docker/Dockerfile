# Production image: builds the front end, resolves PHP dependencies, and serves
# the result from Laravel Octane on Swoole. Octane serves the application and
# the compiled assets in one process, so there is no web server in front of it.
# See .docker/dev/Dockerfile and .docker/compose.yaml for local development.

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22
# Override to pull base images through a registry mirror, e.g.
#   docker build --build-arg REGISTRY=mirror.example.com .
ARG REGISTRY=docker.io

# `--from` cannot expand variables, so the parameterised images are pinned to
# named stages here and referenced by name below.
FROM ${REGISTRY}/mlocati/php-extension-installer:2 AS ext-installer
FROM ${REGISTRY}/node:${NODE_VERSION}-bookworm-slim AS node-base

# ---------------------------------------------------------------------------
# Shared PHP toolchain used by the build stages.
# ---------------------------------------------------------------------------
FROM ${REGISTRY}/php:${PHP_VERSION}-cli-bookworm AS php-build-base

# Optional trust anchors for TLS-inspecting networks. Empty by default — see
# .docker/certs/README.md.
COPY .docker/certs/ /usr/local/share/ca-certificates/
RUN update-ca-certificates
ENV NODE_EXTRA_CA_CERTS=/etc/ssl/certs/ca-certificates.crt

COPY --from=ext-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions bcmath intl zip pcntl opcache @composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

WORKDIR /app

# ---------------------------------------------------------------------------
# node_modules, cached against package-lock.json alone.
# ---------------------------------------------------------------------------
FROM node-base AS node-modules

# Optional trust anchors for TLS-inspecting networks. Empty by default — see
# .docker/certs/README.md.
COPY .docker/certs/ /usr/local/share/ca-certificates/
RUN update-ca-certificates
ENV NODE_EXTRA_CA_CERTS=/etc/ssl/certs/ca-certificates.crt

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci --no-audit --no-fund

# ---------------------------------------------------------------------------
# vendor/, cached against composer.lock alone. Scripts and the autoloader are
# deferred because the application source is not present yet.
# ---------------------------------------------------------------------------
FROM php-build-base AS composer-deps

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress

# ---------------------------------------------------------------------------
# Application build. The Vite build shells out to `php artisan
# wayfinder:generate`, so this stage needs both Node and a bootable PHP app.
# ---------------------------------------------------------------------------
FROM php-build-base AS assets

COPY --from=node-base /usr/local/bin/node /usr/local/bin/node
COPY --from=node-base /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -sf ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY --from=composer-deps /app/vendor ./vendor
COPY --from=node-modules /app/node_modules ./node_modules
COPY . .

# `wayfinder:generate` boots the framework, which wants a syntactically valid
# key and a database driver it can resolve. Neither value reaches the runtime
# image — only the built artefacts are copied out.
ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/tmp/build.sqlite

# The throwaway key is scoped to this layer's shell rather than set with ENV,
# so it never lands in the image's metadata.
RUN export APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
 && touch /tmp/build.sqlite \
 && composer dump-autoload --no-dev --optimize --classmap-authoritative \
 && npm run build \
 && rm -rf node_modules /tmp/build.sqlite .env .docker

# ---------------------------------------------------------------------------
# Runtime.
# ---------------------------------------------------------------------------
FROM ${REGISTRY}/php:${PHP_VERSION}-cli-bookworm AS runtime

# Optional trust anchors for TLS-inspecting networks. Empty by default — see
# .docker/certs/README.md. Also needed at runtime for outbound TLS (mail, S3).
COPY .docker/certs/ /usr/local/share/ca-certificates/
RUN update-ca-certificates

COPY --from=ext-installer /usr/bin/install-php-extensions /usr/local/bin/

# swoole is compiled from PECL here, which is the slow step of this stage.
RUN install-php-extensions bcmath intl zip pcntl opcache swoole-6.2.2 \
 && apt-get update \
 && apt-get install -y --no-install-recommends gosu \
 && rm -rf /var/lib/apt/lists/* /var/www/html

COPY .docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html

COPY --from=assets --chown=www-data:www-data /app /var/www/html

# /var/www/html/storage is normally a volume; the entrypoint recreates this
# tree inside it on every boot.
RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             storage/app/public \
             storage/database \
             bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/storage/database/database.sqlite \
    RUN_MIGRATIONS=false \
    OCTANE_SERVER=swoole

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8000/up")===false?1:0);'

ENTRYPOINT ["entrypoint"]
# --max-requests recycles workers to cap the damage from any leak; Octane's
# default is 500 and is restated here so it is visible at the deploy boundary.
CMD ["php", "artisan", "octane:start", \
     "--server=swoole", "--host=0.0.0.0", "--port=8000", "--max-requests=500"]
