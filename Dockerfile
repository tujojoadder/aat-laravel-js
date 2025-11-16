# Stage 1 - Build Frontend
FROM node:18 AS frontend
WORKDIR /app
COPY package*.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm install && npm run build

# Stage 2 - Apache + PHP
FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock* ./

# Install dependencies with special flags
RUN composer install \
    --ignore-platform-reqs \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader || composer update --ignore-platform-reqs --no-dev --no-interaction

# Copy entire application
COPY . .

# Copy built frontend assets
COPY --from=frontend /app/public/build ./public/build

# Now run composer scripts
RUN composer dump-autoload --optimize --no-dev

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Apache configuration
RUN echo '<VirtualHost *:8080>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Change Apache port
RUN sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf

# Create storage link and optimize
RUN php artisan storage:link || true \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 8080

CMD ["apache2-foreground"]