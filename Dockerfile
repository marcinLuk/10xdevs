FROM php:8.4-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by Laravel
# ctype, dom, fileinfo, json, tokenizer are bundled in PHP 8.2 — no install needed
RUN docker-php-ext-install \
    bcmath \
    mbstring \
    pdo_mysql \
    xml \
    zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Entrypoint uruchamiany przez docker-compose
CMD ["php-fpm"]
