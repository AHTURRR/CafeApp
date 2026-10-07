# Laravel 13 (lock dependency) membutuhkan PHP >= 8.4.1
FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libzip-dev libicu-dev libpng-dev libjpeg-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql bcmath intl zip opcache pcntl gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Menjalankan sebagai user non-root agar file hasil artisan di host tidak dimiliki root.
ARG UID=1000
RUN useradd -m -u ${UID} app && chown -R app:app /var/www/html
USER app
