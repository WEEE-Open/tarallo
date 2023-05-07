FROM php:8.0-fpm-alpine

ARG XDEBUG
ARG PROFILER
ARG DEVELOPMENT

# Expose the arguments as environment variables, in case their value might be useful inside the container
ENV XDEBUG=${XDEBUG}
ENV PROFILER=${PROFILER}
ENV DEVELOPMENT=${DEVELOPMENT}

RUN docker-php-ext-install pdo_mysql
RUN apk add libzip-dev
RUN docker-php-ext-install zip

# Create server root directory and enter it
RUN mkdir -p /var/www/html
WORKDIR /var/www/html

# Download and install Adminer. Also substitute default server host from "localhost" to "db" for convenience
RUN if [ "$DEVELOPMENT" = "true" ]; then\
        mkdir -p /var/www/html/admin \
        && curl -o /var/www/html/admin/index.php -L https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1-mysql-en.php \
        && sed -i "s#'\.h(SERVER)\.'\" title#db\" title#g" /var/www/html/admin/index.php; \
    fi

# Create mountpoints
RUN mkdir ./sql ./bin ./public ./src ./tests

COPY config/config-docker.php ./config/config.php
COPY ./composer.json ./composer.json 
COPY ./composer.lock ./composer.lock
COPY ./phpcs.xml ./phpcs.xml

# install composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Install dependencies. Also install development dependencies if not in production
RUN if [ "$DEVELOPMENT" = "true" ]; \
        then composer install; \
        else composer install --no-dev; \
    fi

# install xdebug
# https://stackoverflow.com/questions/49907308/installing-xdebug-in-docker
RUN if [ "$DEVELOPMENT" = "true" -a "$XDEBUG" = "true" ]; \
        then apk add --no-cache --virtual .phpize_deps $PHPIZE_DEPS linux-headers \
        && yes | pecl install xdebug \
        && docker-php-ext-enable xdebug \
        && echo "xdebug.client_host=$(/sbin/ip route|awk '/default/ { print $3 }')" >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo 'xdebug.discover_client_host=true' >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo 'xdebug.start_with_request=trigger' >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo 'xdebug.output_dir="/xdebug"' >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo 'xdebug.profiler_output_name = "cachegrind_%H_%t.out"' >> /usr/local/etc/php/conf.d/xdebug.ini \
        && apk del -f .phpize_deps; \
    fi

# Conditionally enable the profiler
RUN if [ "$DEVELOPMENT" = "true" -a "$XDEBUG" = "true" -a "$PROFILER" = "true" ]; \
        then mkdir /xdebug && echo 'xdebug.mode=debug,profile' >> /usr/local/etc/php/conf.d/xdebug.ini; \
        else echo 'xdebug.mode=debug' >> /usr/local/etc/php/conf.d/xdebug.ini; \
    fi

# Add git folder if exists, used for the credits page to display the current commit version
COPY .gi[t]/ ./.git/