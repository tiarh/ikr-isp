FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    nginx supervisor curl git unzip libpng-dev libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql gd zip intl opcache bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chmod -R 775 storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 8080
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
