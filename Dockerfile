# Stage 1 - Build with PHP
FROM php:8.2-cli

# Install system dependencies with ALL required libraries for gd
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    default-mysql-client \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Configure and install gd SEPARATELY first (it's the problematic one)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install remaining PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    sodium

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy dependency files first for better Docker layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package*.json ./
RUN npm ci --omit=dev

# Copy application files
COPY . .

# Finalize composer autoload
RUN composer dump-autoload --optimize

# Expose port
EXPOSE 8000

# Run Laravel migrations & start server
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000