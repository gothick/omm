#! /bin/sh

DB_HOST="$(php ./parseurl.php --component=host --url=$DATABASE_URL)"
DB_PORT="$(php ./parseurl.php --component=port --url=$DATABASE_URL)"
ES_HOST="$(php ./parseurl.php --component=host --url=$ELASTICSEARCH_URL)"
ES_PORT="$(php ./parseurl.php --component=port --url=$ELASTICSEARCH_URL)"
RD_HOST="$(php ./parseurl.php --component=host --url=$REDIS_URL)"
RD_PORT="$(php ./parseurl.php --component=port --url=$REDIS_URL)"

./wait-for-it.sh $DB_HOST:$DB_PORT -t 30 && \
	./wait-for-it.sh $RD_HOST:$RD_PORT -t 30
	./wait-for-it.sh $ES_HOST:$ES_PORT -t 120 # Elasticsearch can be very slow to get started
php ./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Start messenger consumer in background
# This isn't needed in the dev environment as the transport mechanism
# is synchronous, so the messages are never sent and there's no queue to
# consume.
if [ "$APP_ENV" != "dev" ]
then
	nohup php ./bin/console messenger:consume async -vv --time-limit=3600 2>&1 &
fi
# start apache as www-data user
#exec sudo -u www-data apache2-foreground
apache2-foreground
