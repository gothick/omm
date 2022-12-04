FROM php:7.4-apache

ENV APP_ENV=dev

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
# TODO Perhaps remove opcache from this list when we add a production layer, and only
# put it in there? Or wouldn't it matter? Depends on the setup, I suppose...
RUN install-php-extensions pdo mysqli pdo_mysql zip apcu bcmath intl gd xdebug opcache @composer-2;

COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY docker/pass-omm-envvars.conf /etc/apache2/conf-available/pass-omm-envvars.conf
RUN a2enconf pass-omm-envvars
# Not much different from the original $PHP_INI_DIR/php.ini-development; so far I've
# just raised the memory limit so our phpunit coverage stuff runs successfully.
COPY docker/php.ini-development "$PHP_INI_DIR/php.ini"

WORKDIR /var/www
# We'll do the composer install in this layer, *then* copy everything
# else in a later layer. That should make sure this layer gets cached
# more effectively. https://dev.to/iacons/faster-docker-builds-with-composer-install-3opj
COPY ./composer.json .
COPY ./composer.lock .
RUN composer install --prefer-dist --no-interaction --no-autoloader --no-scripts \
	# Should get rid of files in /root/.composer/cache that we won't need from here
	&& composer clear-cache -n
COPY . .

# Docker phpunit has some specific needs, e.g. overriding the
# database connection.
COPY phpunit.xml.docker ./phpunit.xml

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
	&& yarn run encore dev
ENTRYPOINT ["/var/www/entrypoint.sh"]
# CMD ["apache2-foreground"]

# TODO: Build a production layer too
