# We use Alpine to keep the image size small
FROM php:8.2-fpm-alpine

# Install system dependencies needed for common PHP extensions, Node.js, and Composer.
# It is a best practice to combine these steps into a single RUN command to reduce image layers.
RUN apk add --no-cache \
    git \
    curl \
    libpng \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql exif \
    # Install Composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www

# Fix permissions for Symfony directories (run at container start)
CMD mkdir -p var/cache var/log && \
    chown -R www-data:www-data var && \
    chmod -R 775 var && \
    php-fpm
