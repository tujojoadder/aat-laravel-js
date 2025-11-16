FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libpq-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install only essential PHP extensions (skip gd if you don't need image manipulation)
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pdo_mysql \
    mbstring \
    bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package*.json ./
RUN npm ci --omit=dev

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000