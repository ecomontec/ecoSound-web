#!/bin/sh

# Run ecoSound-web

docker-compose up -d
docker-compose exec apache chown -R www-data:www-data cache tmp sounds bin
docker-compose exec apache bash -c '
while ! (nc -z queue 5672); do
  echo "Queue is not ready. Waiting...";
  sleep 2;
done;'

echo "Starting queue worker with auto-restart..."

# Start worker in auto-restart loop (survives crashes)
docker-compose exec apache chmod +x /var/www/html/start-worker.sh
docker-compose exec -T -u www-data apache setsid /var/www/html/start-worker.sh < /dev/null &
sleep 2

echo "Worker started. Jobs will be processed sequentially from the queue."
