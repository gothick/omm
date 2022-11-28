FROM php:7.4-apache

# This is important during the build as e.g. clearing the cache
# will try to clear out dev things we haven't built if it things
# the environment is dev.
ENV APP_ENV=docker

RUN a2enmod rewrite

RUN apt-get update \
  	&& apt-get install -y libzip-dev git wget exiftool --no-install-recommends \
  	&& apt-get clean \
	&& curl -fsSL https://deb.nodesource.com/setup_19.x | bash - \
	&& apt-get install -y nodejs \
	&& curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /usr/share/keyrings/yarnkey.gpg >/dev/null \
	&& echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian stable main" | tee /etc/apt/sources.list.d/yarn.list \
	&& apt-get update && apt-get install -y yarn \
  	&& rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
RUN install-php-extensions pdo mysqli pdo_mysql zip apcu bcmath intl gd @composer-2;

COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www
# We'll do the composer install in this layer, *then* copy everything
# else in a later layer. That should make sure this layer gets cached
# more effectively. https://dev.to/iacons/faster-docker-builds-with-composer-install-3opj
COPY ./composer.json .
COPY ./composer.lock .
RUN composer install --prefer-dist --no-dev --no-interaction --no-autoloader --no-scripts

COPY . /var/www

COPY ./docker/wait-for-it.sh .
RUN chmod +x entrypoint.sh
RUN chmod +x wait-for-it.sh
RUN mkdir -p /var/www/var \
  	&& chown -R www-data:www-data /var/www/var \
    && mkdir -p /var/www/public/uploads \
	&& chown -R www-data:www-data /var/www/public/uploads \
    && mkdir -p /var/www/public/uploads/images \
	&& chown -R www-data:www-data /var/www/public/uploads/images
RUN composer dump-autoload --optimize --no-interaction
RUN yarn \
	&& yarn run encore production
ENTRYPOINT ["/var/www/entrypoint.sh"]
# CMD ["apache2-foreground"]
