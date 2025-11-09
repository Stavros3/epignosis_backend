FROM php:8.2-apache

# Enable commonly used Apache module and MySQL extensions for development
RUN a2enmod rewrite \
	&& docker-php-ext-install mysqli pdo_mysql

WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html

EXPOSE 80
