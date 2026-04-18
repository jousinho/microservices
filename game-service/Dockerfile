# ── Imagen base compartida ────────────────────────────────────────────────────
FROM php:8.3-fpm AS base

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        opcache \
        bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-scripts --no-interaction --prefer-dist

COPY . .

# ── Target: php-fpm (sirve peticiones HTTP) ───────────────────────────────────
FROM base AS fpm

EXPOSE 9000
CMD ["php-fpm"]

# ── Target: php-cli (migraciones y comandos Symfony) ─────────────────────────
FROM base AS cli

CMD ["tail", "-f", "/dev/null"]
