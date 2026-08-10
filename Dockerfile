FROM php:8.2-apache

# ------------------------------------------------------------
# Install system dependencies and PHP extensions
# ------------------------------------------------------------

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# Enable Apache rewrite module
# ------------------------------------------------------------

RUN a2enmod rewrite

# ------------------------------------------------------------
# Set Laravel public directory as Apache document root
# ------------------------------------------------------------

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# ------------------------------------------------------------
# Install Composer
# ------------------------------------------------------------

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# Application directory
# ------------------------------------------------------------

WORKDIR /var/www/html

# ------------------------------------------------------------
# Copy Composer files first
# ------------------------------------------------------------

COPY composer.json composer.lock ./

# ------------------------------------------------------------
# Install production dependencies
# ------------------------------------------------------------

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# ------------------------------------------------------------
# Copy Laravel application
# ------------------------------------------------------------

COPY . .

# ------------------------------------------------------------
# Create required Laravel directories
# ------------------------------------------------------------

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# ------------------------------------------------------------
# Set permissions
# ------------------------------------------------------------

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

RUN chmod -R 775 \
    storage \
    bootstrap/cache

# ------------------------------------------------------------
# Expose Apache HTTP port
# ------------------------------------------------------------

EXPOSE 80

# ------------------------------------------------------------
# Run migrations, then start Apache
# ------------------------------------------------------------

CMD ["sh", "-c", "php artisan migrate --force && apache2-foreground"]