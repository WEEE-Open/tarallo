FROM php:fpm

ARG XDEBUG
ARG PROFILER
ARG DEVELOPMENT

# Expose the arguments as environment variables, in case their value might be useful inside the container
ENV XDEBUG=${XDEBUG}
ENV PROFILER=${PROFILER}
ENV DEVELOPMENT=${DEVELOPMENT}

RUN apt-get update && apt-get install -y git unzip iproute2 && docker-php-ext-install pdo_mysql

# Create server root directory and enter it
RUN mkdir -p /var/www/html
WORKDIR /var/www/html
COPY . .
COPY config/config-docker.php ./config/config.php

# Download and install Adminer. Also substitute default server host from "localhost" to "db" for convenience
RUN mkdir -p /var/www/html/admin && curl -o /var/www/html/admin/index.php -L https://github.com/vrana/adminer/releases/download/v4.8.0/adminer-4.8.0-mysql-en.php
RUN sed -i "s#'\.h(SERVER)\.'\" title#db\" title#g" /var/www/html/admin/index.php

# Get IP address of the Docker host
# RUN DOCKER_HOST_IP=$(/sbin/ip route|awk '/default/ { print $3 }'); export DOCKER_HOST_IP

# install composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Install dependencies. Also install development dependencies if not in production
RUN if [ $DEVELOPMENT = "false" ]; \
        then composer install; \
        else composer install --no-dev; \
    fi

# install xdebug
# https://stackoverflow.com/questions/49907308/installing-xdebug-in-docker
RUN if [ $DEVELOPMENT = "true" -a $XDEBUG = "true" ]; \
        then yes | pecl install xdebug \
        && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
        && echo "xdebug.client_host=$(/sbin/ip route|awk '/default/ { print $3 }')" >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo 'xdebug.discover_client_host=true' >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo 'xdebug.xdebug.start_with_request=trigger' >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo 'xdebug.output_dir="/xdebug"' >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo 'xdebug.profiler_output_name = "cachegrind_%H_%t.out"' >> /usr/local/etc/php/conf.d/xdebug.ini; \
    fi

# Conditionally enable the profiler
RUN if [ $DEVELOPMENT = "true" -a $XDEBUG = "true" -a $PROFILER = "true" ]; \
        then mkdir /xdebug && echo 'xdebug.mode=debug,profile' >> /usr/local/etc/php/conf.d/xdebug.ini; \
        else echo 'xdebug.mode=debug' >> /usr/local/etc/php/conf.d/xdebug.ini; \
    fi