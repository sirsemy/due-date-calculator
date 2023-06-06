# start with the official Composer image and name it
FROM composer:2.5.7 AS composer

ENV COMPOSER_ALLOW_SUPERUSER 1
#ENV DEBIAN_FRONTEND noninteractive
ENV TZ="Europe/Budapest"
#ENV NODE_VERSION=16

# setup base image
FROM php:8.1-fpm
LABEL maintener="Sirsemy <universe.hajdu@gmail.com>"

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# install debian dependencies
RUN apt-get update && \
    apt-get install -y \
    libmcrypt-dev \
    default-mysql-client \
    libmagickwand-dev --no-install-recommends \
    libonig-dev \
    libzip-dev

RUN apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python2

# install PHP extensions
RUN docker-php-ext-install \
    mbstring \
    pdo_mysql \
    zip \
    bcmath

# install mcrypt
RUN pecl install mcrypt-1.0.6 && \
    docker-php-ext-enable mcrypt

# install imagick
RUN pecl install imagick && \
    docker-php-ext-enable imagick

RUN pecl install xdebug-3.2.1

# copy the Composer PHAR from the Composer image into the PHP image
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN  mkdir /var/www/html/due_date_calculator
WORKDIR /var/www/html/due_date_calculator

RUN useradd -ms /bin/bash admin
USER admin

CMD bash -c "php artisan serve --host 0.0.0.0 --port 80"

EXPOSE 80
