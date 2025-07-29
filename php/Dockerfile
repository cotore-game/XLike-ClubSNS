# php/Dockerfile
FROM php:8.2-fpm

# 必要なシステムライブラリをインストール
# libzip-dev: zip 拡張機能の依存
# libmysqlclient-dev: pdo_mysql 拡張機能の依存
# libonig-dev: mbstring 拡張機能の依存
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libmariadb-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*
    # aptキャッシュをクリーンアップしてイメージサイズを削減

# 必要な PHP 拡張機能をインストール
RUN docker-php-ext-install pdo_mysql zip mbstring

# カスタム php.ini をコピー
COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html
