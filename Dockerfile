FROM php:8.2-apache

# 1. Cài đặt các extension PHP cần thiết và công cụ giải nén (Bao gồm cả PostgreSQL driver)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql gd bcmath

# 2. Cài đặt Composer trực tiếp vào trong Container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Bật mod_rewrite của Apache cho Laravel
RUN a2enmod rewrite

# 4. Sửa DocumentRoot của Apache sang thư mục public của Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 5. Thiết lập thư mục làm việc và copy code vào
WORKDIR /var/www/html
COPY . /var/www/html/

# 6. XÓA FILE LOCK CŨ VÀ CHẠY COMPOSER UPDATE ĐỂ SINH RA THƯ MỤC VENDOR
RUN rm -f composer.lock && composer update --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs

# 7. Cấp quyền ghi cho thư mục storage và bootstrap/cache để Laravel chạy mượt mà
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Cấu hình Port theo Render
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

EXPOSE ${PORT}

# 9. SỬ DỤNG LỆNH KHỞI ĐỘNG CỦA APACHE (Bỏ qua việc dọn cache tại bước build này)
CMD ["apache2-foreground"]