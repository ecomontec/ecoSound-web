#!/bin/sh

# Install BioSounds

git clone https://github.com/kahst/BirdNET-Analyzer.git ./src/BirdNET-Analyzer
git clone https://github.com/macaodha/batdetect2.git ./src/batdetect2

docker-compose up -d
docker-compose exec apache composer install
docker-compose exec apache bash -c '
while ! (nc -z database 3306); do
  echo "Database is not ready. Waiting...";
  sleep 2;
done;'

echo "Database started."

docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds < init.sql
docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds < data.sql
docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds < gadm.sql
docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds < world_seas.sql

echo "Data imported"
