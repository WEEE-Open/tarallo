FROM php:fpm

RUN apt-get update && apt-get install -y git unzip && docker-php-ext-install pdo_mysql
#RUN docker-php-ext-install pecl-xdebug

RUN mkdir -p /var/www/html
WORKDIR /var/www/html

COPY composer.* ./
COPY *.sql ./
COPY bin/ ./bin/
COPY public/ ./public/
COPY src/ ./src/
COPY tests/ ./tests/

RUN mkdir config
COPY config/config-docker.php ./config/config.php

RUN mkdir -p /var/www/html/admin && curl -o /var/www/html/admin/index.php -L https://github.com/vrana/adminer/releases/download/v4.8.0/adminer-4.8.0-mysql-en.php

# install composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
# install dependencies
RUN composer install --no-dev
