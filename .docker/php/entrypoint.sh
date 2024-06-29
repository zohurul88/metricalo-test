#!/bin/sh
set -e

export APP_ENV=${APP_ENV:-dev}

if [ "$APP_ENV" = "prod" ]; then
    echo "Running in production mode"
    composer install --no-dev --optimize-autoloader --classmap-authoritative
    php bin/console cache:clear --env=prod --no-debug
else
    echo "Running in development mode"
    composer install -vv
    php bin/console cache:clear --env=dev
fi

exec php-fpm
