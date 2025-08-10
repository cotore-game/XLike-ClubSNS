# php/Dockerfile
FROM php:8.2-fpm

# 必要なPHP拡張機能と、その依存ライブラリをまとめてインストール
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libmariadb-dev \
    libonig-dev \
    && docker-php-ext-install pdo_mysql zip mbstring \
    && rm -rf /var/lib/apt/lists/*
    
# カスタム php.ini をコピー
COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html