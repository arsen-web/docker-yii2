FROM php:7.4-fpm-alpine

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

COPY ./conf.d $PHP_INI_DIR/conf.d

WORKDIR /app
