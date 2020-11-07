FROM php:7.4-fpm-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN apk update \
    && apk upgrade \
    && apk add --no-cache autoconf g++ make geoip-dev \
    && pecl install xdebug geoip-1.1.1 \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable xdebug \
    && install-php-extensions gd pgsql intl bcmath imap opcache zip imagick uuid

RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

COPY conf.d $PHP_INI_DIR/conf.d

WORKDIR /app
