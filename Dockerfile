FROM php:7.4-apache

RUN a2enmod rewrite

RUN apt-get update \
  && apt-get install -y libzip-dev git wget --no-install-recommends \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
RUN install-php-extensions pdo mysqli pdo_mysql zip apcu bcmath intl @composer-2;

COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY . /var/www

WORKDIR /var/www
RUN echo "APP_ENV=prod\nAPP_SECRET=$(echo $RANDOM | md5)" > .env.local
RUN composer install --prefer-dist --no-dev --no-interaction
RUN mkdir -p /var/www/var \
  && chown -R www-data:www-data /var/www/var

CMD ["apache2-foreground"]
