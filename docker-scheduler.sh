#!/bin/sh
set -eu

cd "${APP_BASE_PATH:-/app}"

children=''
sleep_pid=''

stop_scheduler() {
    trap '' INT TERM

    if [ -n "$sleep_pid" ]; then
        kill -TERM "$sleep_pid" 2>/dev/null || :
        wait "$sleep_pid" 2>/dev/null || :
    fi

    for child in $children; do
        kill -TERM "$child" 2>/dev/null || :
    done

    for child in $children; do
        wait "$child" 2>/dev/null || :
    done

    exit 0
}

trap stop_scheduler INT TERM

while :; do
    active_children=''

    for child in $children; do
        if kill -0 "$child" 2>/dev/null; then
            active_children="$active_children $child"
        else
            wait "$child" 2>/dev/null || :
        fi
    done

    children="$active_children"

    # Align each launch to the next minute without keeping Laravel booted while idle.
    delay=$((60 - $(date +%s) % 60))
    sleep "$delay" &
    sleep_pid=$!
    wait "$sleep_pid" || :
    sleep_pid=''

    # Laravel's per-task locks preserve minute scheduling during a longer cleanup.
    php -d "memory_limit=${SCHEDULER_MEMORY_LIMIT:-128M}" artisan schedule:run --no-interaction &
    children="$children $!"
done
