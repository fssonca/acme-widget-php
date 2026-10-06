# Multi-architecture official images; resolved versions are recorded in docs/versions.md.
FROM node:24-bookworm-slim@sha256:d6aa754f16b3197301076f047b5def2f02ea1dbbc2ca920407d46d7ec7f87b20 AS frontend-tools
WORKDIR /workspace/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./

FROM frontend-tools AS frontend-build
RUN npm run build

FROM composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS composer-bin

FROM php:8.4-apache-bookworm@sha256:9e811795a606ca7f8586dee48d32acf444985c19ac252d70d181cd71a4603898 AS php-base
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/acme-entrypoint
COPY docker/healthcheck.php /usr/local/bin/healthcheck.php
RUN chmod 755 /usr/local/bin/acme-entrypoint
WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
ENTRYPOINT ["acme-entrypoint"]

FROM php-base AS backend-tools
COPY backend/composer.json backend/composer.lock ./
RUN composer install --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY backend/ ./
RUN composer dump-autoload --optimize --no-interaction \
    && composer check-platform-reqs
CMD ["php", "artisan", "test"]

FROM php-base AS backend-runtime
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY backend/ ./
RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && composer check-platform-reqs --no-dev

FROM php-base AS app
COPY --from=backend-runtime /var/www/html/ ./
COPY --from=frontend-build /workspace/backend/public/app/ ./public/app/
RUN install -d -o www-data -g www-data -m 775 \
      storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
CMD ["apache2-foreground"]
