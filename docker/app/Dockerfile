FROM php:8.4-apache-bookworm

# 本番(kagoya: PHP 8.4 + Apache)に合わせる。画像は Imagick で WebP・HEIC を扱う

# 開発 PC のウイルス対策が HTTPS を検査する場合のルート証明書(docker/certs/*.crt。Git には入れない。無くてもよい)
COPY docker/certs/ /usr/local/share/ca-certificates/extra/
RUN update-ca-certificates

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y --no-install-recommends git unzip zip default-mysql-client \
    && install-php-extensions pdo_mysql zip intl gd exif bcmath opcache pcntl imagick \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/app/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/app/php.ini /usr/local/etc/php/conf.d/zz-doinaka.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
