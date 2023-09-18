FROM php:8.1.2-apache

# Install packages and extensions
RUN apt-get -qq update && \
    apt-get -y install curl git wget vim unzip libzip-dev libpq-dev libicu-dev apt-transport-https ca-certificates gnupg npm \
      libengine-pkcs11-openssl python3 python3-pip \
    && pip3 install python-pkcs11 \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip opcache pdo pdo_pgsql mysqli pdo_mysql

RUN a2enmod rewrite && \
    a2enmod headers && \
    a2enmod proxy && \
    a2enmod proxy_http && \
    a2enmod rewrite deflate

# Install Composer globally for increased performance
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer self-update

# Copy assets and sources
COPY shared/assets/api/default.conf /etc/apache2/sites-available/000-default.conf
COPY shared/assets/api/entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

## shared
COPY shared/assets/api/api.ini /usr/local/etc/php/conf.d/api.ini
COPY shared/assets/api/security.conf /etc/apache2/conf-available/security.conf
COPY shared/assets/api/apache2.conf /etc/apache2/apache2.conf
COPY shared/assets/api/ports.conf /etc/apache2/ports.conf
COPY shared/assets/api/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY shared/assets/api/mpm_prefork_default.conf /etc/apache2/mods-available/mpm_prefork_default.conf

## hsm
COPY messaging-app/assets/cs_pkcs11_R2.cfg /etc/utimaco/cs_pkcs11_R2.cfg
COPY messaging-app/assets/libcs_pkcs11_R3.so /usr/lib/utimaco/libcs_pkcs11_R3.so
ENV PKCS_MODULE=/usr/lib/utimaco/libcs_pkcs11_R3.so
ENV SECURITY_MODULE_SLOT_LABEL="CryptoServer PKCS11 Token"

COPY shared/repositories /shared/repositories
COPY messaging-app/src /src
COPY shared/library /shared/library

RUN cd /src && composer install && \
    usermod -u 1000 www-data && \
    chgrp -R www-data /src/var/cache && \
    chmod -R ug+rwx /src/var/cache

# logs should go to stdout / stderr
RUN set -ex \
    && ln -sf /proc/1/fd/1 "/var/log/cron.log" \
    && ln -sf /proc/1/fd/1 "/var/log/syslog" \
    && chown -R --no-dereference "www-data:www-data" /src/var/cache

# Environment
ENV PATH $PATH:/root/.composer/vendor/bin

WORKDIR /src

EXPOSE 8080/tcp

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
