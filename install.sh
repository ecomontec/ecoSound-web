#!/bin/sh

docker-compose up -d
docker-compose exec apache composer install
docker-compose exec apache bash -c '
while ! (nc -z database 3306); do
  echo "Database is not ready. Waiting...";
  sleep 2;
done;'

echo "Database started."

echo "Importing database schema (init.sql)..."
docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds < init.sql
echo "✓ Schema imported"

echo "Importing test data (data.sql)..."
docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds < data.sql
echo "✓ Test data imported"

echo "========================================"
echo "Importing geographic reference data..."
echo "GADM (countries/regions) + World Seas"
echo "Estimated time: 7-15 minutes"
echo "Please wait..."
echo "========================================"
cat gadm.sql | docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds
echo "✓ GADM data imported"
cat world_seas.sql | docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds
echo "✓ World seas data imported"

# Install BirdNET-Analyzer
git clone https://github.com/kahst/BirdNET-Analyzer.git ./src/BirdNET-Analyzer
cd ./src/BirdNET-Analyzer
git checkout 9c2f852

echo "Data imported"
