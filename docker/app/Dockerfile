FROM php:fpm

# Disable xdebug by default
ARG XDEBUG=false

RUN apt-get update && apt-get install -y git unzip && docker-php-ext-install pdo_mysql
#RUN docker-php-ext-install pecl-xdebug

# Create server root directory and enter it
RUN mkdir -p /var/www/html
WORKDIR /var/www/html

# Create directories for served content
# These will be filled in with content from the git tree on the host on docker-compose up
RUN mkdir public
RUN mkdir src
RUN mkdir tests

COPY composer.* ./
COPY *.sql ./
COPY bin/ ./bin/

RUN mkdir config
COPY config/config-docker.php ./config/config.php

RUN mkdir -p /var/www/html/admin && curl -o /var/www/html/admin/index.php -L https://github.com/vrana/adminer/releases/download/v4.8.0/adminer-4.8.0-mysql-en.php

# install composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
# install dependencies
RUN composer install --no-dev
# install xdebug
# https://stackoverflow.com/questions/49907308/installing-xdebug-in-docker
RUN if [ $XDEBUG = "true" ]; \
    then yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo 'xdebug.remote_enable = 1' >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo 'xdebug.remote_connect_back = 1' >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo 'xdebug.profiler_enable_trigger = 1' >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo 'xdebug.profiler_enable = 0' >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo 'xdebug.profiler_output_dir = "/xdebug"' >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo 'xdebug.profiler_output_name = "cachegrind_%H_%t.out"' >> /usr/local/etc/php/conf.d/xdebug.ini; \
    fi
