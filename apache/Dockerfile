# apache/Dockerfile
FROM httpd:2.4

# 必要なシステムライブラリをインストール
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Apacheの設定ファイルをコピー
COPY conf.d/php.conf /usr/local/apache2/conf/extra/httpd-php.conf

# メイン設定ファイルに含める
RUN echo "Include conf/extra/httpd-php.conf" >> /usr/local/apache2/conf/httpd.conf