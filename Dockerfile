# ─────────────────────────────────────────────────────────────────────────────
# Stage 1: Composer — Install PHP dependencies
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2 AS vendor-builder
WORKDIR /app
COPY composer.json composer.lock ./
# Install deps tanpa script dulu (karena butuh full app untuk script Laravel)
RUN composer install --no-dev --ignore-platform-reqs --no-scripts --prefer-dist

# ─────────────────────────────────────────────────────────────────────────────
# Stage 2: Node — Build frontend assets (butuh Ziggy dari vendor)
# ─────────────────────────────────────────────────────────────────────────────
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --prefer-offline

COPY . .
# Ambil vendor hasil install stage 1 agar Vite bisa temukan vendor/tightenco/ziggy
COPY --from=vendor-builder /app/vendor ./vendor
RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
# Stage 3: PHP — Base image dengan semua extensions
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm AS base

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip supervisor cron \
    # GD / Imagick
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libmagickwand-dev imagemagick \
    # XML / ZIP / SOAP
    libxml2-dev libzip-dev \
    # Intl / GMP
    libicu-dev libgmp-dev \
    # Memcached
    libmemcached-dev \
    # String
    libonig-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions (built-in)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring exif pcntl bcmath \
        gd zip xml intl gmp opcache sockets

# PHP extensions (PECL)
RUN pecl install imagick redis apcu memcached igbinary msgpack \
    && docker-php-ext-enable imagick redis apcu memcached igbinary msgpack

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config: opcache + production settings
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Working directory
WORKDIR /var/www

# ─────────────────────────────────────────────────────────────────────────────
# Stage 4: Final — App image
# ─────────────────────────────────────────────────────────────────────────────
FROM base AS app

# Copy app source (tanpa node_modules, .env, storage/logs — via .dockerignore)
COPY . /var/www

# Copy vendor dari vendor-builder
COPY --from=vendor-builder /app/vendor /var/www/vendor

# Copy built frontend dari stage node-builder
COPY --from=node-builder /app/public/build /var/www/public/build

# Fake environment untuk mencegah error saat menjalankan artisan command di tahap build
ENV REVERB_APP_ID=fake_id
ENV REVERB_APP_KEY=fake_key
ENV REVERB_APP_SECRET=fake_secret
ENV APP_KEY=base64:fakekeyfakekeyfakekeyfakekeyfakekeyfakekey=
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=:memory:

# Jalankan autoloader optimization & scripts
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Expose PHP-FPM port
EXPOSE 9000

CMD ["php-fpm"]
