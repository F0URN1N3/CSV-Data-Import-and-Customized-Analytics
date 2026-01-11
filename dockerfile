FROM php:8.2-apache

# 1. 安裝系統依賴
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    curl \
    gnupg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql gd zip curl bcmath xml

# 2. 安裝 Node.js (Vite 編譯需要)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

RUN a2enmod rewrite

# 3. 複製程式碼
COPY . /var/www/html
WORKDIR /var/www/html

# 4. 安裝 Composer 依賴
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --optimize-autoloader --no-scripts --ignore-platform-reqs

# 5. 安裝 NPM 依賴並執行 Vite 編譯 (解決 ViteManifestNotFoundException)
RUN npm install
RUN npm run build

# 6. 設定目錄權限
RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage \
    /var/www/html/bootstrap/cache

# 7. Apache 設定
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 8. 針對大檔案上傳優化 PHP 設定
RUN echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 80

CMD php artisan config:cache && \
    php artisan view:cache && \
    php artisan migrate --force && \
    apache2-foreground
