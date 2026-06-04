# ==========================================
# Stage 1: Aplikasi Utama Laravel API
# ==========================================
FROM php:8.3-fpm-alpine

# Install system dependencies & PHP extensions yang dibutuhkan API
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    libzip-dev \
    postgresql-dev

# Install PHP extensions (MySQL, PostgreSQL, dll)
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Ambil Composer terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy semua source code ke dalam container
COPY . .

# Install Composer dependencies untuk Production
# --no-dev akan memastikan package testing seperti Faker/Mockery tidak ikut terinstall
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions agar folder storage bisa ditulis oleh web server
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy konfigurasi Nginx dan Supervisor
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port 80 untuk traffic HTTP (Coolify akan mengarah ke sini)
EXPOSE 80

# Jalankan Nginx dan PHP-FPM secara bersamaan via Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
