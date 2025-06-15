FROM php:8.2-apache


RUN apt-get update && apt-get install -y unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


COPY . /var/www/html/


WORKDIR /var/www/html


RUN test -f composer.json


RUN composer install --no-dev --optimize-autoloader


RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/src|g' /etc/apache2/sites-available/000-default.conf


RUN a2enmod rewrite


RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html


EXPOSE 80


CMD ["apache2-foreground"]
