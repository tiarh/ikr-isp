# =============================================================================
# Stage 1: Build frontend assets with Node
# =============================================================================
FROM node:20-alpine AS frontend

WORKDIR /app

# Install deps dulu (cache layer)
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then \
      npm ci --prefer-offline --no-audit --no-fund; \
    else \
      npm install --prefer-offline --no-audit --no-fund; \
    fi

# Copy source & build
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ ./resources/
RUN npm run build

# =============================================================================
# Stage 2: Composer install (production deps only)
# =============================================================================
FROM composer:2 AS vendor

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install --no-dev + optimize autoloader
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --no-security-blocking \
    --ignore-platform-req=ext-exif \
    --ignore-platform-req=ext-gd \
    --ignore-platform-req=ext-intl

# Copy app source for autoload + post-autoload scripts
COPY . /app
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# =============================================================================
# Stage 3: Final runtime — php-fpm + nginx + queue worker
# =============================================================================
FROM webdevops/php-nginx:8.4-alpine

# Install required PHP extensions + system deps
RUN apk add --no-cache \
    supervisor \
    mysql-client \
    redis \
    bash \
    tini \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip

# Configure OPcache for production
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /app

# Copy built vendor + autoloader
COPY --from=vendor /app/vendor ./vendor

# Copy built frontend assets
COPY --from=frontend /app/public/build ./public/build

# Copy application code
COPY . /app

# Copy custom nginx config
# Copy our nginx config (overrides webdevops defaults)
# webdevops/php-nginx has a default 10-php.conf with <PHP_SOCKET> template
# that fails to render. We need to either remove that or use it.
# Our custom config uses 127.0.0.1:9000 directly (more portable).
COPY docker/nginx-prod.conf /etc/nginx/conf.d/default.conf
# Remove webdevops default conf.d files (they reference <PHP_SOCKET> placeholder
# or try to include /opt/docker/etc/nginx/vhost.conf which doesn't exist in our setup)
RUN rm -f /opt/docker/etc/nginx/conf.d/10-php.conf \
         /opt/docker/etc/nginx/vhost.common.d/10-php.conf \
         /opt/docker/etc/nginx/vhost.conf \
         /etc/nginx/conf.d/10-docker.conf \
         /etc/nginx/conf.d/10-docker-secure.conf 2>/dev/null || true

# Copy supervisord config (php-fpm + nginx + queue + scheduler)
COPY docker/supervisord-prod.conf /etc/supervisord.conf

# Generate optimized configs
RUN php artisan package:discover --ansi || true

# Permissions
# Create ALL standard Laravel writable directories explicitly (not brace expansion,
# because Alpine busybox sh doesn't expand braces the same as bash).
RUN mkdir -p \
        /app/bootstrap/cache \
        /app/storage/app/public \
        /app/storage/framework/cache/data \
        /app/storage/framework/sessions \
        /app/storage/framework/testing \
        /app/storage/framework/views \
        /app/storage/logs \
        /app/storage/app \
    && chown -R application:application /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

# Expose HTTP port
EXPOSE 8080

# Tini = proper signal handling + zombie reaping
ENTRYPOINT ["/sbin/tini", "--"]

# Supervisord runs all 4 services (php-fpm, nginx, queue, scheduler)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf", "-n"]
