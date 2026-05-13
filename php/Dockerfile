FROM php:8.3-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS curl-dev \
    && docker-php-ext-install pdo_mysql mysqli curl \
    && apk del $PHPIZE_DEPS

COPY portal /var/www/html
WORKDIR /var/www/html
