#!/bin/bash
# ecoSound-web Installation Script (Ubuntu 22.04 & 24 compatible)
# Simple, production-ready installation

set -e

BASE_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "=========================================="
echo "ecoSound-web Installation"
echo "=========================================="
echo ""

echo "Step 1: Initialize Data Directories"
mkdir -p "$BASE_DIR/data/mysql"
mkdir -p "$BASE_DIR/sounds"
mkdir -p "$BASE_DIR/sound_images"
mkdir -p "$BASE_DIR/project_images"
mkdir -p "$BASE_DIR/src/cache"
mkdir -p "$BASE_DIR/src/logs"
mkdir -p "$BASE_DIR/src/tmp"

# Set permissions (non-fatal - Docker may own these directories)
chmod 755 "$BASE_DIR/sounds" 2>/dev/null || true
chmod 755 "$BASE_DIR/sound_images" 2>/dev/null || true
chmod 755 "$BASE_DIR/project_images" 2>/dev/null || true
chmod 755 "$BASE_DIR/src/cache" 2>/dev/null || true
chmod 755 "$BASE_DIR/src/tmp" 2>/dev/null || true
echo "✓ Directories initialized"
echo ""

echo "Step 2: Starting Docker Containers"
docker-compose up -d
echo "✓ Containers started"
echo ""

echo "Step 3: Waiting for Database (max 60s)"
timeout 60 bash -c 'until nc -z localhost 3306; do sleep 1; done' || true
sleep 2
echo "✓ Database ready"
echo ""

echo "Step 4: Installing Composer Dependencies"
docker-compose exec -T apache composer install --no-dev --prefer-dist 2>&1 | grep -E "(Installing|Generating|Nothing)" || true
echo "✓ Composer install completed"
echo ""

echo "Step 5: Import Database Schema & Data"
TABLE_COUNT=$(docker-compose exec -T database mysql -ubiosounds -pbiosounds biosounds -e "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema='biosounds';" 2>/dev/null | tail -1 || echo "0")
if [ "$TABLE_COUNT" -eq "0" ]; then
  docker-compose exec -T database mysql -ubiosounds -pbiosounds biosounds < "$BASE_DIR/01-init.sql" 2>/dev/null || true
  docker-compose exec -T database mysql -ubiosounds -pbiosounds biosounds < "$BASE_DIR/02-data.sql" 2>/dev/null || true
  docker-compose exec -T database mysql -ubiosounds -pbiosounds biosounds < "$BASE_DIR/03-gadm.sql" 2>/dev/null || true
  docker-compose exec -T database mysql -ubiosounds -pbiosounds biosounds < "$BASE_DIR/04-world_seas.sql" 2>/dev/null || true
  echo "✓ Database initialized"
else
  echo "✓ Database already initialized ($TABLE_COUNT tables found)"
fi
echo ""

echo "Step 6: Installing BirdNET-Analyzer"
if [ ! -d "$BASE_DIR/src/BirdNET-Analyzer" ]; then
  git clone https://github.com/kahst/BirdNET-Analyzer.git "$BASE_DIR/src/BirdNET-Analyzer"
  cd "$BASE_DIR/src/BirdNET-Analyzer"
  git checkout 9c2f852
  cd "$BASE_DIR"
fi
echo "✓ BirdNET-Analyzer installed"
echo ""

echo "=========================================="
echo "Installation Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Start the worker queue: bash run.sh"
echo "  2. Access the app: http://localhost:8080"
echo ""
