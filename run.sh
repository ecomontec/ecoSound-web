#!/bin/sh

# Run BioSounds

docker-compose up -d
docker-compose exec apache chown -R www-data:www-data cache tmp sounds bin
docker-compose exec apache bash -c '
while ! (nc -z queue 5672); do
  echo "Queue is not ready. Waiting...";
  sleep 2;
done;'

echo "Queue worker started."

docker-compose exec -T -u www-data apache nohup php worker.php > files_update.log 2>&1 &
