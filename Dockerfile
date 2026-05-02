# syntax=docker/dockerfile:1

FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

FROM php:8.4-fpm AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        gd \
        mbstring \
        opcache \
        pdo_mysql \
        xml \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/sprintlog-entrypoint

RUN chmod +x /usr/local/bin/sprintlog-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public/build

ENTRYPOINT ["sprintlog-entrypoint"]
CMD ["php-fpm"]
