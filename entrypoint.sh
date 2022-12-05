#! /bin/sh
./wait-for-it.sh docker-database:3306 -t 30 && \
	./wait-for-it.sh elasticsearch:9200 -t 120 # Elasticsearch can be very slow to get started
php ./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Start messenger consumer in background
# This isn't needed in the dev environment as the transport mechanism
# is synchronous, so the messages are never sent and there's no queue to
# consume.
if [ "$APP_ENV" != "dev"]
then
	nohup php ./bin/console messenger:consume async -vv --time-limit=3600 2>&1 &
fi
# start apache
apache2-foreground
