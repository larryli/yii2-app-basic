FROM composer AS composer

COPY composer.* /app/

RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer \
    && composer global require --optimize-autoloader --no-progress --prefer-dist hirak/prestissimo

RUN composer install --no-progress --no-dev --no-scripts --no-suggest --no-interaction --prefer-dist --optimize-autoloader

COPY . /app

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

FROM php:fpm-alpine AS builder

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && apk add -U --no-cache git \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) iconv \
        intl \
        gd \
        pdo_mysql \
        zip

FROM php:fpm-alpine

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && apk add -U --no-cache freetype icu-libs libjpeg-turbo libzip

COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/

COPY --chown=www-data:www-data . /app

COPY --from=composer --chown=www-data:www-data /app/vendor /app/vendor

USER www-data

WORKDIR /app