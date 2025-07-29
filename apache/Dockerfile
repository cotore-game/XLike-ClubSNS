# apache/Dockerfile
FROM httpd:2.4
# Apache 公式イメージを使用

# PHP-FPM モジュールを有効にするために必要なモジュールをインストール
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Apache の設定ファイルをコピー (PHP-FPM との連携設定)
COPY conf.d/php.conf /etc/apache2/conf-enabled/php.conf
