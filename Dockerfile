FROM composer AS composer

COPY composer.* /app/

RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer \
    && composer global require --optimize-autoloader --no-progress --prefer-dist hirak/prestissimo

RUN composer install --no-progress --no-dev --no-scripts --no-suggest --no-interaction --prefer-dist --optimize-autoloader

ARG ASSET_COMPRESS=false

RUN if [ "$ASSET_COMPRESS" = true ]; then \
    composer global require --optimize-autoloader --no-progress --prefer-dist matthiasmullie/minify:1.3.59; \
  fi

COPY . /app

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

RUN if [ "$ASSET_COMPRESS" = true ]; then \
    cp -r /app/vendor/bower-asset/bootstrap/dist /app/web/bootstrap \
    && /app/yii asset/compress /app/config/assets.php /app/config/asset-bundles.php \
    && rm -rf /app/web/css /app/web/js /app/web/bootstrap/css /app/web/bootstrap/js /app/vendor/bower-asset /app/vendor/npm-asset; \
  fi

FROM php:fpm-alpine AS builder

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && apk add -U --no-cache git \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl \
        gd \
        pdo_mysql \
        zip

FROM php:fpm-alpine

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && apk add -U --no-cache freetype icu-libs libjpeg-turbo libzip

COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/

COPY --from=composer --chown=www-data:www-data /app /app

USER www-data

WORKDIR /app