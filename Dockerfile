FROM php:8.2-cli

WORKDIR /var/www/html

COPY . .

RUN apt-get update && apt-get install -y unzip git libzip-dev \
    && docker-php-ext-install pdo_mysql zip

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer

RUN composer install

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
