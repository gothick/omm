#! /bin/sh
./wait-for-it.sh docker-database:3306 -t 30
php ./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Start messenger consumer in background
nohup php ./bin/console messenger:consume async -vv --time-limit=3600 2>&1 &
# start apache
apache2-foreground
