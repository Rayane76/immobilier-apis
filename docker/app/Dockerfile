# ─────────────────────────────────────────────────────────────────────────────
# Stage 1 – Composer dependencies
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# ─────────────────────────────────────────────────────────────────────────────
# Stage 2 – Application image
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.2-cli-bullseye

# ── System dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    supervisor \
    curl \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    bcmath \
    mbstring \
    intl \
    xml \
    pcntl \
    sockets \
    exif \
    opcache

# ── PHP production-tuned ini ──────────────────────────────────────────────────
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

RUN { \
    echo 'upload_max_filesize=100M'; \
    echo 'post_max_size=100M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=60'; \
    } > /usr/local/etc/php/conf.d/app.ini

# ── Application code ──────────────────────────────────────────────────────────
WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

COPY --from=vendor /app/vendor ./vendor

# ── RoadRunner binary ─────────────────────────────────────────────────────────
# Downloads the RoadRunner server binary for linux/amd64 via spiral/roadrunner-cli.
# --no-config prevents overwriting our curated .rr.yaml.
RUN php vendor/bin/rr get-binary \
    --no-config \
    --os=linux \
    --arch=amd64 \
    --location=/var/www/html \
    && chmod +x ./rr

# ── Copy Docker support files ─────────────────────────────────────────────────
COPY docker/app/rr.yaml            ./.rr.yaml
COPY docker/app/supervisord.conf   /etc/supervisor/conf.d/supervisord.conf
COPY docker/app/entrypoint.sh      /entrypoint.sh
RUN  chmod +x /entrypoint.sh

# ── Storage & cache writable dirs + fix ownership for all app files ───────────
RUN mkdir -p \
    storage/logs \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
