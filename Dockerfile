# Frontend: install dependencies and build React into backend/public/app.
FROM node:24-bookworm-slim AS frontend
WORKDIR /frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

# Base: PHP and Apache, shared by the backend tools and the final image.
FROM php:8.4-apache-bookworm AS base
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html

# Backend: all Composer dependencies, used for tests and static analysis.
FROM base AS backend
RUN apt-get update && apt-get install -y --no-install-recommends unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader
COPY backend/ ./
RUN composer dump-autoload --optimize

# Production vendor: same source without dev packages.
FROM backend AS backend-production
RUN composer install --no-dev --no-interaction --optimize-autoloader

# App: a clean base plus the production backend and the built frontend.
FROM base AS app
COPY --from=backend-production --chown=www-data:www-data /var/www/html ./
COPY --from=frontend /backend/public/app ./public/app
