FROM php:8.1-apache

# Install necessary libraries and PHP extensions
RUN apt-get update && apt-get install -y \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy your application files
COPY . /var/www/html

# Set the working directory
WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install
