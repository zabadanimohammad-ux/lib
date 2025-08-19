# استخدم PHP CLI مع دعم MySQL
FROM php:8.2-cli

WORKDIR /var/www/html

# تثبيت امتدادات PHP المطلوبة و unzip
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_mysql

# نسخ المشروع إلى الحاوية
COPY . .

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# تشغيل Laravel عند بدء الحاوية
CMD php artisan serve --host 0.0.0.0 --port $PORT
