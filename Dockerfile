FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libmcrypt-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /usr/src/myapp

COPY . .

RUN composer install --no-scripts --no-interaction -v

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public/"]
