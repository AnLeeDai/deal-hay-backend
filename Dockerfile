# syntax=docker/dockerfile:1.7

FROM composer:2 AS composer

FROM dunglas/frankenphp:1-php8.4-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor unzip \
    && install-php-extensions \
        exif \
        gd \
        pcntl \
        pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY . .

RUN composer install \
    --classmap-authoritative \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    && mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        /config/caddy \
        /data/caddy \
    && useradd --create-home --uid 1000 app \
    && chown -R app:app /app /config /data

COPY Caddyfile /etc/frankenphp/Caddyfile
COPY php.production.ini "$PHP_INI_DIR/conf.d/zz-deal-hay.ini"
COPY supervisord.conf /etc/supervisor/conf.d/deal-hay.conf
COPY supervisord.worker.conf /etc/supervisor/conf.d/deal-hay-worker.conf

# Render uses an unprivileged HTTP port and may reject file capabilities.
RUN setcap -r /usr/local/bin/frankenphp \
    && chmod 755 /app/docker-entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    DEPLOY_ROLE=web \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    PORT=10000 \
    REVERB_SERVER_HOST=0.0.0.0 \
    QUEUE_NAMES=realtime,images,default

USER app

RUN frankenphp version

EXPOSE 10000

HEALTHCHECK NONE

ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["start"]
