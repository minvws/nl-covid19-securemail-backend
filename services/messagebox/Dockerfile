ARG INSTALL_DEV_TOOLS=0
ARG INSTALL_XDEBUG=0
# Will be passed when building in ci action (see release.yml)
ARG APP_VERSION="latest"

# Build backend sources
FROM composer:2.2.6 as backend
ARG INSTALL_DEV_TOOLS
WORKDIR /src
COPY shared/repositories /shared/repositories

COPY messagebox/src/composer.json messagebox/src/composer.lock /src/
RUN if [ "$INSTALL_DEV_TOOLS" = "1" ] ; then \
        composer install \
            --ignore-platform-reqs \
            --no-ansi \
            --no-autoloader \
            --no-interaction ;\
    else \
        composer install \
            --ignore-platform-reqs \
            --no-ansi \
            --no-autoloader \
            --no-interaction \
            --no-dev \
            --no-scripts ;\
    fi

COPY messagebox/src/ /src/
COPY shared/library /shared/library
RUN composer dump-autoload --optimize --classmap-authoritative

# Build frontend assets
FROM node:16.2.0 as frontend
WORKDIR /src

COPY messagebox/src/babel.config.js messagebox/src/package.json messagebox/src/package-lock.json messagebox/src/tsconfig.json messagebox/src/webpack.mix.js messagebox/src/vetur.config.js messagebox/src/vti-diagnostics.js /src/
RUN npm ci

COPY messagebox/src/resources /src/resources
RUN cd /src && npm run production

# Build main image
FROM php:8.1.2-apache
ARG INSTALL_DEV_TOOLS
ARG APP_VERSION
ARG INSTALL_XDEBUG

# Make the build-arg available in runtime
ENV APP_VERSION=$APP_VERSION

# Install packages and extensions
RUN apt update

RUN apt-get -qq update && \
    apt-get -y install curl git wget vim unzip libzip-dev libpq-dev libicu-dev apt-transport-https ca-certificates gnupg npm libaio1 ssl-cert libgmp-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip opcache pdo pdo_pgsql mysqli pdo_mysql gmp

RUN a2enmod rewrite && \
    a2enmod headers && \
    a2enmod proxy && \
    a2enmod proxy_http && \
    a2dismod -f deflate

# Copy assets and sources
COPY messagebox/assets/default.conf /etc/apache2/sites-available/000-default.conf
COPY messagebox/assets/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
COPY messagebox/assets/entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh
COPY messagebox/assets/php.ini /usr/local/etc/php/conf.d/php.ini
COPY messagebox/assets/security.conf /etc/apache2/conf-available/security.conf
COPY messagebox/assets/apache2.conf /etc/apache2/apache2.conf
COPY messagebox/assets/ports.conf /etc/apache2/ports.conf
COPY messagebox/assets/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY messagebox/assets/mpm_prefork_default.conf /etc/apache2/mods-available/mpm_prefork_default.conf

WORKDIR /src
COPY --from=backend /src /src
COPY --from=backend /shared/repositories /shared/repositories
COPY --from=backend /shared/library /shared/library
COPY --from=frontend /src/public /src/public

RUN usermod -u 1000 www-data && \
    chgrp -R www-data /src/storage /src/bootstrap/cache && \
    chmod -R ug+rwx /src/storage /src/bootstrap/cache

# logs should go to stdout / stderr
RUN set -ex \
    && ln -sf /proc/1/fd/1 "/var/log/cron.log" \
    && ln -sf /proc/1/fd/1 "/var/log/syslog"

ENV PATH $PATH:/root/.composer/vendor/bin

RUN if [ "$INSTALL_DEV_TOOLS" = "1" ] ; then \
        a2enmod ssl && \
        a2ensite default-ssl && \
        curl -fsSL https://deb.nodesource.com/setup_16.x | bash - && \
	    apt-get install -y nodejs && \
        npm install npm@6 -g && \
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    fi

RUN if [ "$INSTALL_XDEBUG" = "1" ] ; then \
        pecl install xdebug && \
        docker-php-ext-enable xdebug ;\
    fi


EXPOSE 8080/tcp
EXPOSE 8443/tcp

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
