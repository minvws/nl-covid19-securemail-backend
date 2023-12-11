#!/usr/bin/env bash
cd /src

if [ ! -z "$(php -m | grep xdebug)" ]; then
    export PHP_IDE_CONFIG='serverName=docker_securemail'
    export XDEBUG_MODE="$XDEBUG_MODE"
    export XDEBUG_CONFIG="client_host=$XDEBUG_CLIENT_HOST"
    export XDEBUG_SESSION="docker_securemail"
fi

if [ "$APP_ENV" != "production" ]; then
    echo "Restoring default MPM prefork settings (not running in production)"
    cp -f /etc/apache2/mods-available/mpm_prefork_default.conf /etc/apache2/mods-available/mpm_prefork.conf

    echo "Disabling opcache"
    if command -v phpdismod &> /dev/null; then
        phpdismod opcache
    else
        rm  /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
    fi
fi

exec "$@"
