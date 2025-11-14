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

echo "Initializing externalized data directories..."
echo "Location: $DATA_DIR"
echo ""

# Create data directories
mkdir -p "$DATA_DIR/mysql"

echo "✓ Created data directory structure:"
echo "  - $DATA_DIR/mysql (for MySQL database files)"

# Set proper permissions for MySQL
if [ -d "$DATA_DIR/mysql" ]; then
    # MySQL container runs as user 999:999 (in standard mysql:8.0 image)
    # We make it readable/writable by all for development
    # For production, restrict: sudo chown 999:999 $DATA_DIR/mysql && chmod 700 $DATA_DIR/mysql
    chmod 755 "$DATA_DIR/mysql"
    echo "✓ Set permissions for MySQL directory (755)"
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
