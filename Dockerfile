FROM php:8.1-apache

ENV TZ="America/Argentina/Buenos_Aires"

ARG AMBIENTE

ENV APACHE_DOCUMENT_ROOT /var/www/known-online-challenge

## Install container dependencies
RUN apt-get update && apt-get install -y git \
unzip \
libcurl4-openssl-dev \
openssl \
pkg-config \
libssl-dev \
vim \
w3m \
&& rm -rf /var/lib/apt/lists/*

## Install mongo and mysql extensions for apache2
RUN pecl install mongodb
RUN docker-php-ext-install curl mysqli pdo pdo_mysql
RUN docker-php-ext-enable mongodb mysqli pdo_mysql

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

## Install composer
RUN curl -sS https://getcomposer.org/installer | php -- \
  --install-dir=/usr/bin --filename=composer

WORKDIR ${APACHE_DOCUMENT_ROOT}

EXPOSE 80 

ADD src/ ${APACHE_DOCUMENT_ROOT}

ADD ./docker-files/config/000-default.conf /etc/apache2/sites-available/
ADD ./docker-files/config/apache2.conf /etc/apache2/
ADD docker-files/config/ports.conf /etc/apache2/
RUN chmod -R 777 ${APACHE_DOCUMENT_ROOT}/storage
ADD ./docker-files/config/php.ini /usr/local/etc/php/php.ini
RUN a2enmod rewrite

ADD ./docker-files/scripts/entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]