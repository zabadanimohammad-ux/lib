# استخدم صورة PHP مع Apache
FROM php:8.2-apache

# تثبيت الإضافات المطلوبة لـ Laravel
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# تثبيت Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ضبط Apache
RUN a2enmod rewrite
COPY . /var/www/html
WORKDIR /var/www/html

# ضبط صلاحيات Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# تثبيت المكتبات
RUN composer install --no-dev --optimize-autoloader

# إعداد Apache Document Root لمجلد public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# المنفذ
EXPOSE 80

# تشغيل Apache
CMD ["apache2-foreground"]
