# Laravel API for Dokploy (Build Type: Dockerfile, Build Path: /backend)
FROM php:8.4-cli

# gd = QR code images, pdo_pgsql = Neon, pdo_mysql = MySQL
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libfreetype6-dev libpq-dev libzip-dev unzip git \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo_mysql pdo_pgsql zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chmod -R 777 storage bootstrap/cache

EXPOSE 8000
# env vars come from Dokploy's Environment tab at runtime
CMD php artisan config:clear && php artisan serve --host=0.0.0.0 --port=8000
