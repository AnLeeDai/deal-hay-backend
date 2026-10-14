#!/bin/sh

set -eu

cd "${APP_BASE_PATH:-/app}"

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

if [ "$#" -gt 0 ] && [ "$1" != "start" ] && [ "$1" != "web" ] && [ "$1" != "web-worker" ] && [ "$1" != "reverb" ] && [ "$1" != "worker" ]; then
    exec "$@"
fi

role="${DEPLOY_ROLE:-web}"

if [ "${1:-start}" != "start" ]; then
    role="$1"
fi

case "$role" in
    web|web-worker|reverb|worker)
        ;;
    *)
        echo "Unsupported DEPLOY_ROLE: $role" >&2
        exit 1
        ;;
esac

if [ "${APP_ENV:-production}" = "production" ] \
    && { [ "$role" = "reverb" ] || [ "${BROADCAST_CONNECTION:-}" = "reverb" ]; }; then
    missing_reverb_variables=""

    for variable in REVERB_APP_ID REVERB_APP_KEY REVERB_APP_SECRET; do
        if [ -z "$(printenv "$variable" || true)" ]; then
            missing_reverb_variables="${missing_reverb_variables} $variable"
        fi
    done

    if [ -n "$missing_reverb_variables" ]; then
        echo "Missing required Reverb environment variables:$missing_reverb_variables. Configure matching values on the API and Reverb services." >&2
        exit 1
    fi
fi

if [ "$role" = "reverb" ]; then
    export REVERB_SERVER_HOST=0.0.0.0
    export REVERB_SERVER_PORT="${PORT:-10000}"
fi

case "${RUN_MIGRATIONS:-false}" in
    true|1)
        case "$role" in
            web|web-worker)
                php artisan migrate --force --no-interaction
                ;;
            *)
                echo "RUN_MIGRATIONS can only be enabled for the web or web-worker role." >&2
                exit 1
                ;;
        esac
        ;;
    false|0|"")
        ;;
    *)
        echo "RUN_MIGRATIONS must be true, false, 1, or 0." >&2
        exit 1
        ;;
esac

php artisan optimize --no-interaction

case "$role" in
    web)
        exec supervisord -c /etc/supervisor/conf.d/deal-hay.conf
        ;;
    web-worker)
        exec supervisord -c /etc/supervisor/conf.d/deal-hay-web-worker.conf
        ;;
    reverb)
        exec php artisan reverb:start --host="$REVERB_SERVER_HOST" --port="$REVERB_SERVER_PORT" --no-interaction
        ;;
    worker)
        exec supervisord -c /etc/supervisor/conf.d/deal-hay-worker.conf
        ;;
esac
