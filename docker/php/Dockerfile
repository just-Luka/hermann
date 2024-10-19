# Dockerfile for PHP
FROM php:8.3-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip gd

# Install Composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/symfony

# Set the permissions
RUN chown -R www-data:www-data /var/www/symfony

# Copy existing application directory to /var/www/symfony
COPY . /var/www/symfony

# Expose port 9000 and start PHP-FPM server
EXPOSE 9000
CMD ["php-fpm"]