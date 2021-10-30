#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.
#
# If you have user-specific configurations you would like
# to apply, you may also create user-customizations.sh,
# which will be run after this script.

# If you're not quite ready for Node 12.x
# Uncomment these lines to roll back to
# v11.x or v10.x

# Remove Node.js v12.x:
#sudo apt-get -y purge nodejs
#sudo rm -rf /usr/lib/node_modules/npm/lib
#sudo rm -rf //etc/apt/sources.list.d/nodesource.list

# Install Node.js v11.x
#curl -sL https://deb.nodesource.com/setup_11.x | sudo -E bash -
#sudo apt-get install -y nodejs

# Install Node.js v10.x
#curl -sL https://deb.nodesource.com/setup_10.x | sudo -E bash -
#sudo apt-get install -y nodejs

# Refresh packages & upgrade to latest stuff
sudo apt-get update
sudo apt-get -y upgrade

# Useful packages
sudo apt-get -y install emacs-nox

# Necessary packages
sudo apt-get -y install exiftool

# Elasticsearch
sudo wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
echo "deb https://artifacts.elastic.co/packages/7.x/apt stable main" | sudo tee /etc/apt/sources.list.d/elastic-7.x.list
sudo apt-get -y install elasticsearch

# Increase service startup timeout
sudo mkdir /etc/systemd/system/elasticsearch.service.d
echo -e '[Service]\nTimeoutStartSec=600' | sudo tee /etc/systemd/system/elasticsearch.service.d/startup-timeout.conf

sudo systemctl daemon-reload
sudo systemctl enable elasticsearch.service
sudo systemctl start elasticsearch.service

# Allow bigger file uploads
sudo echo "client_max_body_size 20M;" > /etc/nginx/conf.d/nginx.conf
sudo systemctl reload nginx

# More elbow-room for Composer
# sudo echo "memory_limit = 4096M" > /etc/php/7.4/mods-available/30-matt-increase-memory.ini
sudo bash -c 'echo "memory_limit = 4096M" > /etc/php/7.4/mods-available/increase_php_cli_memory.ini'
sudo phpenmod -v 7.4 -s cli increase_php_cli_memory

# Let's have the command-line back to 7.4, too; Doctrine migrations were failing with 8
# https://laracasts.com/discuss/channels/servers/vagranthomestead-setting-up-multiple-php-versions
# https://github.com/doctrine/DoctrineMigrationsBundle/issues/393
sudo ln -sf /usr/bin/php7.4 /usr/bin/php

# And enable the xdebug extension on the cli for phpunit coverage
sudo tee /etc/php/7.4/mods-available/enable_debug_coverage.ini > /dev/null <<EOT
zend_extension=xdebug.so                                                                                                           
xdebug.mode=coverage
EOT
sudo phpenmod -v 7.4 -s cli enable_debug_coverage
