#! /bin/sh

DB_HOST="$(php ./parseurl.php --component=host --url=$DATABASE_URL)"
DB_PORT="$(php ./parseurl.php --component=port --url=$DATABASE_URL)"
ES_HOST="$(php ./parseurl.php --component=host --url=$ELASTICSEARCH_URL)"
ES_PORT="$(php ./parseurl.php --component=port --url=$ELASTICSEARCH_URL)"
RD_HOST="$(php ./parseurl.php --component=host --url=$REDIS_URL)"
RD_PORT="$(php ./parseurl.php --component=port --url=$REDIS_URL)"

echo "Waiting for servers"

./wait-for-it.sh $DB_HOST:$DB_PORT -t 30 && \
	./wait-for-it.sh $RD_HOST:$RD_PORT -t 30 && \
	./wait-for-it.sh $ES_HOST:$ES_PORT -t 120 # Elasticsearch can be very slow to get started

echo "Servers up, running tests"
php -dxdebug.mode=coverage bin/phpunit --cache-directory /tmp/phpunitcache --testdox
