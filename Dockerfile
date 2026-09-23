# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# base : PHP-FPM 8.5 avec les extensions dont l'application a besoin
# ---------------------------------------------------------------------------
FROM php:8.5-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl zip bcmath opcache pcntl \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/app.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# vendor : dépendances PHP de production + découverte des packages
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts \
    && php artisan package:discover

# ---------------------------------------------------------------------------
# assets : build Vite (Tailwind scanne aussi une vue de pagination du framework)
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources resources
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---------------------------------------------------------------------------
# app : image exécutée par les services « app » (PHP-FPM) et « worker »
# ---------------------------------------------------------------------------
FROM base AS app

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build public/build

RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/app-entrypoint

USER www-data

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# web : Caddy (HTTPS automatique) qui sert public/ et passe PHP à « app »
# ---------------------------------------------------------------------------
FROM caddy:2-alpine AS web

COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=app /var/www/html/public /var/www/html/public
