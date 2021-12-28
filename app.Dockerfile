FROM php:7.3-apache

RUN docker-php-ext-install mysqli

RUN a2enmod rewrite

# RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80