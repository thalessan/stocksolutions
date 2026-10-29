FROM php:8.1-apache

# Dependencias de compilacao de gd, zip e imagick (Saude do Site exige gd/zip)
RUN apt-get update && apt-get install -y --no-install-recommends \
      libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
      libzip-dev libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install mysqli pdo_mysql gd zip \
    && docker-php-ext-enable mysqli pdo_mysql gd zip \
    && pecl install imagick && docker-php-ext-enable imagick && rm -rf /usr/src/php*

# WP precisa de mod_rewrite para permalinks
RUN a2enmod rewrite

# Docroot = raiz do WordPress (web/)
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
