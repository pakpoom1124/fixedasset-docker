FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    default-mysql-client \
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd

RUN a2enmod rewrite

COPY php.ini /usr/local/etc/php/php.ini
COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html