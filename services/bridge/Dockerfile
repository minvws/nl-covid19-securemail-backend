FROM php:8.1.2-cli

# Install packages and extensions
RUN apt-get -qq update && \
    apt-get -y install curl git wget vim unzip libzip-dev libpq-dev libicu-dev apt-transport-https ca-certificates gnupg \
                       openssl opensc libengine-pkcs11-openssl apt-utils \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install opcache

# Install composer globally for increased performance
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer self-update

COPY bridge/src /src
COPY shared/repositories /shared/repositories
RUN cd /src && composer install

# Environment
ENV PATH $PATH:/root/.composer/vendor/bin

# Logs should go to stdout / stderr
RUN set -ex \
    && ln -sf /proc/1/fd/1 "/var/log/cron.log" \
    && ln -sf /proc/1/fd/1 "/var/log/syslog"

WORKDIR /src

CMD ["/src/bin/bridge"]
