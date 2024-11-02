# Dockerfile for PHP
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip gd \
    && docker-php-ext-install sockets

# Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); exit(1); } echo PHP_EOL;" && \
    php composer-setup.php --version=2.7.7 --install-dir=/usr/local/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

WORKDIR /var/www/hermann

# Copy existing application directory to /var/www/symfony
COPY . /var/www/hermann

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer update --no-interaction --prefer-dist --optimize-autoloader

# Expose port 8000 and start PHP-FPM server
EXPOSE 8000
CMD ["php-fpm"]