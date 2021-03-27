FROM php:fpm

RUN mkdir -p /var/www/html
WORKDIR /var/www/html

COPY ../../composer.* .
COPY ../../bin/* ./bin/
COPY ../../public/* ./public/
COPY ../../src/* ./src/
COPY ../../tests/* ./tests/
COPY ../../utils/provision/composer.sh .

RUN mkdir config
COPY ../../config/config-docker.php ./config/config.php

# install composer
RUN ./composer.sh
# install dependencies
RUN composer install --no-dev --no-suggest
