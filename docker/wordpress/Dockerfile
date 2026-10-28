FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo_mysql && docker-php-ext-enable mysqli pdo_mysql

# WP precisa de mod_rewrite para permalinks
RUN a2enmod rewrite

# Docroot = raiz do WordPress (web/)
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
