#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
    export APP_KEY
fi

php artisan config:clear --no-interaction

if [ "${1:-}" = "apache2-foreground" ]; then
    php artisan config:cache --no-interaction
fi

exec "$@"
