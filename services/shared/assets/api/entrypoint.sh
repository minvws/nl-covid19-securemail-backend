#!/usr/bin/env bash

cd /src

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
