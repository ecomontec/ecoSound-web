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

BASE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Create data directories
mkdir -p "$BASE_DIR/mysql"
mkdir -p "$BASE_DIR/sounds"
mkdir -p "$BASE_DIR/sound_images"
mkdir -p "$BASE_DIR/project_images"

# Set proper permissions
# MySQL container runs as user 999:999 (in standard mysql:8.0 image)
# We make it readable/writable by all for development
# For production, restrict: sudo chown 999:999 $BASE_DIR/mysql && chmod 700 $BASE_DIR/mysql
chmod 755 "$BASE_DIR/mysql"
chmod 755 "$BASE_DIR/sounds"
chmod 755 "$BASE_DIR/sound_images"
chmod 755 "$BASE_DIR/project_images"


