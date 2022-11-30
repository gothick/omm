#! /bin/sh
./wait-for-it.sh docker-database:3306 -t 30 && \
	./wait-for-it.sh elasticsearch:9200 -t 60
php ./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Start messenger consumer in background
# TODO: This isn't needed in the dev environment as the tranport mechanism
# is synchronous, so the messages are never sent and there's no queue to
# consume.
nohup php ./bin/console messenger:consume async -vv --time-limit=3600 2>&1 &
# start apache
apache2-foreground
