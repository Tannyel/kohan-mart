FROM php:8.3-apache

# 1. Install system dependencies, PostgreSQL drivers, and Node.js
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libpq-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# 2. Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# 3. Point Apache DocumentRoot to Laravel's /public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Copy project files into container
WORKDIR /var/www/html
COPY . .

# 6. Install PHP & frontend dependencies and build Vite CSS/JS
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# 7. Fix folder permissions for Laravel logs & cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Cache config/routes, run database migrations, and start Apache
CMD php artisan config:cache && php artisan route:cache && php artisan migrate --force && apache2-foreground