#!/bin/sh

set -eu

cd /app

if [ "${APP_ENV:-production}" = "production" ] && [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be configured before running a production command or service." >&2
    exit 1
fi

if [ "${APP_ENV:-production}" != "production" ] \
    && [ "${DB_CONNECTION:-}" = "sqlite" ] \
    && [ -n "${DB_DATABASE:-}" ] \
    && [ "${DB_DATABASE}" != ":memory:" ]; then
    mkdir -p "$(dirname "${DB_DATABASE}")"
    touch "${DB_DATABASE}"
fi

if [ "$#" -gt 0 ] && [ "$1" != "start" ] && [ "$1" != "web" ] && [ "$1" != "worker" ]; then
    exec "$@"
fi

role="${DEPLOY_ROLE:-web}"

if [ "${1:-start}" != "start" ]; then
    role="$1"
fi

php artisan optimize --no-interaction

case "$role" in
    web)
        exec /usr/bin/supervisord -c /etc/supervisor/conf.d/deal-hay.conf
        ;;
    worker)
        exec /usr/bin/supervisord -c /etc/supervisor/conf.d/deal-hay-worker.conf
        ;;
    *)
        echo "Unsupported DEPLOY_ROLE: $role" >&2
        exit 1
        ;;
esac
