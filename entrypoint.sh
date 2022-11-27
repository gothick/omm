#! /bin/sh
./wait-for-it.sh docker-database:3306 -t 30
php ./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# start apache
apache2-foreground
