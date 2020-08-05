# Set ubuntu base image
FROM php:7.4-fpm

# RUN echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;
COPY ./custom.ini /usr/local/etc/php/conf.d/custom.ini

#RUN apt-get update && apt-get install -y \
#        libpng-dev \
#        libgmp3-dev \
#        libxml2-dev \
#        libcurl4-openssl-dev \

#RUN docker-php-source extract \
#    && docker-php-ext-install -j$(nproc) mbstring gd pdo pdo_mysql opcache curl zip \
#    && docker-php-source delete

RUN docker-php-ext-install pdo pdo_mysql
