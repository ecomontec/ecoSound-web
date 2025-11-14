#!/bin/bash

# Initialize externalized data directories for Docker containers
# 
# USAGE:
#   bash init-data-dirs.sh
#
# WHEN TO USE THIS SCRIPT:
#   ✅ Fresh installation (no existing data)
#   ✅ Clean re-initialization (starting from scratch)
#   ✅ New development machine (setting up from git clone)
#
# WHEN NOT TO USE THIS SCRIPT:
#   ❌ Migrating from old named volumes (see UPGRADE_MIGRATION_GUIDE.md)
#   ❌ Directory already has database files
#   ❌ Restoring from backup
#
# This script ensures the ./data directory structure exists and has proper permissions.

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DATA_DIR="$SCRIPT_DIR/data"
MEDIA_DIR="$SCRIPT_DIR/media"

echo "Initializing externalized data directories..."
echo "Location: $DATA_DIR and $MEDIA_DIR"
echo ""

# Create data directories
mkdir -p "$DATA_DIR/mysql"
mkdir -p "$MEDIA_DIR/sounds"
mkdir -p "$MEDIA_DIR/images"
mkdir -p "$MEDIA_DIR/projects"

echo "✓ Created data directory structure:"
echo "  - $DATA_DIR/mysql (for MySQL database files)"
echo "  - $MEDIA_DIR/sounds (for audio recordings)"
echo "  - $MEDIA_DIR/images (for spectrograms)"
echo "  - $MEDIA_DIR/projects (for project images)"

# Set proper permissions for MySQL
if [ -d "$DATA_DIR/mysql" ]; then
    # MySQL container runs as user 999:999 (in standard mysql:8.0 image)
    # We make it readable/writable by all for development
    # For production, restrict: sudo chown 999:999 $DATA_DIR/mysql && chmod 700 $DATA_DIR/mysql
    chmod 755 "$DATA_DIR/mysql"
    echo "✓ Set permissions for MySQL directory (755)"
fi

# Set permissions for media directories
if [ -d "$MEDIA_DIR" ]; then
    chmod 755 "$MEDIA_DIR"
    chmod 755 "$MEDIA_DIR/sounds"
    chmod 755 "$MEDIA_DIR/images"
    chmod 755 "$MEDIA_DIR/projects"
    echo "✓ Set permissions for media directories (755)"
fi

echo ""
echo "✅ Data directories initialized successfully!"
echo ""
echo "NEXT STEPS:"
echo ""
echo "For fresh installation:"
echo "  1. docker-compose up -d"
echo "  2. ./install.sh"
echo ""
echo "For detailed migration guide, see: UPGRADE_MIGRATION_GUIDE.md"
echo ""
