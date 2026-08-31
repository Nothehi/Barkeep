# Development image. The project is bind-mounted over /app at run time, so this
# only has to supply the toolchain: PHP with dev extensions, Composer, and Node
# for the Vite dev server.

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22
# Override to pull base images through a registry mirror.
ARG REGISTRY=docker.io

# `--from` cannot expand variables, so the parameterised images are pinned to
# named stages here and referenced by name below.
FROM ${REGISTRY}/mlocati/php-extension-installer:2 AS ext-installer
FROM ${REGISTRY}/node:${NODE_VERSION}-bookworm-slim AS node-base

FROM ${REGISTRY}/php:${PHP_VERSION}-cli-bookworm

# Optional trust anchors for TLS-inspecting networks. Empty by default — see
# .docker/certs/README.md.
COPY .docker/certs/ /usr/local/share/ca-certificates/
RUN update-ca-certificates
ENV NODE_EXTRA_CA_CERTS=/etc/ssl/certs/ca-certificates.crt

COPY --from=ext-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions bcmath intl zip pcntl opcache pcov swoole-6.2.2 @composer \
 && apt-get update \
 && apt-get install -y --no-install-recommends git unzip sqlite3 gosu \
 && rm -rf /var/lib/apt/lists/*

COPY --from=node-base /usr/local/bin/node /usr/local/bin/node
COPY --from=node-base /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -sf ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# The container user is resolved at run time from the owner of the bind mount
# rather than baked in, because the right answer differs by Docker flavour:
# rootless already maps the host user onto root, while rootful needs a user
# with the host's own UID or every generated file lands root-owned.
RUN mkdir -p /home/app/.composer /home/app/.npm

COPY .docker/dev/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/home/app/.composer \
    NPM_CONFIG_CACHE=/home/app/.npm

WORKDIR /app

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "octane:start", \
     "--server=swoole", "--host=0.0.0.0", "--port=8000", "--watch"]
