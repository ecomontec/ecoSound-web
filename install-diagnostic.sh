#!/bin/bash

# ecoSound-web Installation Script with Diagnostics
# Enhanced version for troubleshooting Ubuntu 22.04 installations
# Usage: bash install-diagnostic.sh 2>&1 | tee install.log

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ============================================================================
# SYSTEM DIAGNOSTICS
# ============================================================================

log_info "=========================================="
log_info "ecoSound-web Installation Diagnostics"
log_info "=========================================="
log_info ""

log_info "1. System Information"
log_info "OS: $(uname -s)"
log_info "Kernel: $(uname -r)"
log_info "Architecture: $(uname -m)"
log_info ""

log_info "2. Disk Space"
df -h | grep -E "^/dev|Filesystem" || true
log_info ""

log_info "3. Docker Installation Check"
if command -v docker &> /dev/null; then
    log_success "Docker is installed"
    docker --version
else
    log_error "Docker is NOT installed"
    exit 1
fi
log_info ""

log_info "4. Docker Compose Check"
if command -v docker-compose &> /dev/null; then
    log_success "docker-compose is installed"
    docker-compose --version
else
    log_error "docker-compose is NOT installed"
    exit 1
fi
log_info ""

log_info "5. Docker Daemon Status"
if docker ps > /dev/null 2>&1; then
    log_success "Docker daemon is running"
    docker info | grep -E "Server Version|Containers:|Images:|Storage Driver" || true
else
    log_error "Cannot connect to Docker daemon"
    log_error "Make sure Docker daemon is running: sudo systemctl start docker"
    exit 1
fi
log_info ""

log_info "6. Required Commands Check"
REQUIRED_CMDS=("nc" "git" "curl")
for cmd in "${REQUIRED_CMDS[@]}"; do
    if command -v "$cmd" &> /dev/null; then
        log_success "$cmd is available"
    else
        log_warning "$cmd is NOT available (will be needed for full functionality)"
    fi
done
log_info ""

# ============================================================================
# DIRECTORY INITIALIZATION
# ============================================================================

log_info "=========================================="
log_info "Step 1: Initialize Data Directories"
log_info "=========================================="
log_info ""

BASE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
log_info "Working directory: $BASE_DIR"

log_info "Creating directory structure..."
mkdir -p "$BASE_DIR/data/mysql"
mkdir -p "$BASE_DIR/sounds"
mkdir -p "$BASE_DIR/sound_images"
mkdir -p "$BASE_DIR/project_images"
mkdir -p "$BASE_DIR/src/cache"
mkdir -p "$BASE_DIR/src/logs"
mkdir -p "$BASE_DIR/src/tmp"
log_success "Directories created"

log_info "Setting directory permissions..."
chmod 755 "$BASE_DIR/data/mysql"
chmod 755 "$BASE_DIR/sounds"
chmod 755 "$BASE_DIR/sound_images"
chmod 755 "$BASE_DIR/project_images"
chmod 755 "$BASE_DIR/src/cache"
chmod 755 "$BASE_DIR/src/logs"
chmod 755 "$BASE_DIR/src/tmp"
log_success "Permissions set to 755"

log_info "Verifying directory permissions..."
ls -ld "$BASE_DIR/data/mysql" "$BASE_DIR/sounds" "$BASE_DIR/sound_images" "$BASE_DIR/project_images" "$BASE_DIR/src/cache" "$BASE_DIR/src/logs" "$BASE_DIR/src/tmp"
log_info ""

# ============================================================================
# DOCKER COMPOSE UP
# ============================================================================

log_info "=========================================="
log_info "Step 2: Starting Docker Containers"
log_info "=========================================="
log_info ""

log_info "Running: docker-compose up -d"
docker-compose up -d
log_success "Containers started"
log_info ""

log_info "Waiting for containers to be ready..."
sleep 5

log_info "Container status:"
docker-compose ps
log_info ""

# ============================================================================
# APACHE SETUP
# ============================================================================

log_info "=========================================="
log_info "Step 3: Setting up Apache & Composer"
log_info "=========================================="
log_info ""

log_info "Getting Apache container ID..."
APACHE_CONTAINER=$(docker ps -q -f ancestor=biosounds-apache 2>/dev/null || docker-compose ps -q apache)
if [ -z "$APACHE_CONTAINER" ]; then
    log_error "Could not find Apache container"
    exit 1
fi
log_info "Apache container: $APACHE_CONTAINER"

log_info "Installing Composer dependencies..."
docker-compose exec -T apache composer install 2>&1 | head -20
log_success "Composer install completed"
log_info ""

# ============================================================================
# DATABASE READINESS
# ============================================================================

log_info "=========================================="
log_info "Step 4: Waiting for Database"
log_info "=========================================="
log_info ""

log_info "Waiting for MySQL to be ready on port 3306..."
TIMEOUT=60
ELAPSED=0
while ! (timeout 1 bash -c "echo >/dev/tcp/127.0.0.1/13306" 2>/dev/null || nc -z localhost 13306 2>/dev/null); do
    echo "  Database not ready yet... (${ELAPSED}s)"
    sleep 2
    ELAPSED=$((ELAPSED + 2))
    if [ $ELAPSED -gt $TIMEOUT ]; then
        log_error "Database failed to start within ${TIMEOUT}s"
        log_error "Docker logs for database:"
        docker-compose logs database | tail -50
        exit 1
    fi
done
log_success "Database is ready (${ELAPSED}s to startup)"
log_info ""

log_info "Testing database connection..."
docker-compose exec database mysql -ubiosounds -pbiosounds biosounds -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    log_success "Database connection successful"
else
    log_error "Database connection failed"
    exit 1
fi
log_info ""

# ============================================================================
# DATABASE INITIALIZATION
# ============================================================================

log_info "=========================================="
log_info "Step 5: Initializing Database"
log_info "=========================================="
log_info ""

INIT_FILES=("01-init.sql" "02-data.sql" "03-gadm.sql" "04-world_seas.sql")

for sql_file in "${INIT_FILES[@]}"; do
    if [ ! -f "$BASE_DIR/$sql_file" ]; then
        log_error "Required file not found: $sql_file"
        exit 1
    fi
    
    log_info "Importing $sql_file..."
    docker exec -i "$(docker ps -q -f ancestor=mysql)" mysql -ubiosounds -pbiosounds biosounds < "$BASE_DIR/$sql_file"
    log_success "$sql_file imported successfully"
done
log_info ""

log_info "Verifying database tables..."
TABLE_COUNT=$(docker-compose exec -T database mysql -ubiosounds -pbiosounds biosounds -e "SHOW TABLES;" 2>/dev/null | wc -l)
log_info "Number of tables: $((TABLE_COUNT - 1))"
log_info ""

# ============================================================================
# BIRDNET ANALYZER
# ============================================================================

log_info "=========================================="
log_info "Step 6: Installing BirdNET-Analyzer"
log_info "=========================================="
log_info ""

if [ -d "$BASE_DIR/src/BirdNET-Analyzer" ]; then
    log_warning "BirdNET-Analyzer directory already exists, skipping clone"
else
    log_info "Cloning BirdNET-Analyzer..."
    cd "$BASE_DIR/src"
    git clone https://github.com/kahst/BirdNET-Analyzer.git
    cd BirdNET-Analyzer
    log_info "Checking out specific commit..."
    git checkout 9c2f852
    log_success "BirdNET-Analyzer installed"
fi
log_info ""

# ============================================================================
# WORKER QUEUE SETUP
# ============================================================================

log_info "=========================================="
log_info "Step 7: RabbitMQ Queue Setup"
log_info "=========================================="
log_info ""

log_info "Waiting for RabbitMQ to be ready..."
TIMEOUT=60
ELAPSED=0
while ! (timeout 1 bash -c "echo >/dev/tcp/127.0.0.1/5672" 2>/dev/null || nc -z localhost 5672 2>/dev/null); do
    echo "  RabbitMQ not ready yet... (${ELAPSED}s)"
    sleep 2
    ELAPSED=$((ELAPSED + 2))
    if [ $ELAPSED -gt $TIMEOUT ]; then
        log_error "RabbitMQ failed to start within ${TIMEOUT}s"
        log_error "Docker logs for queue:"
        docker-compose logs queue | tail -50
        exit 1
    fi
done
log_success "RabbitMQ is ready (${ELAPSED}s to startup)"
log_info ""

log_info "Container status check:"
docker-compose ps
log_info ""

# ============================================================================
# FINAL VERIFICATION
# ============================================================================

log_info "=========================================="
log_info "Installation Complete!"
log_info "=========================================="
log_info ""

log_success "✓ Data directories initialized"
log_success "✓ Docker containers running"
log_success "✓ Database initialized"
log_success "✓ Apache/PHP configured"
log_success "✓ RabbitMQ queue ready"
log_info ""

log_info "Access the application:"
log_info "  Web UI: http://localhost:8080"
log_info ""

log_info "Next steps:"
log_info "  1. Open http://localhost:8080 in your browser"
log_info "  2. Check browser console for any JavaScript errors"
log_info "  3. Try uploading a test recording"
log_info ""

log_info "Useful commands:"
log_info "  View logs:        docker-compose logs -f"
log_info "  View logs (apache): docker-compose logs -f apache"
log_info "  View logs (db):     docker-compose logs -f database"
log_info "  View logs (queue):  docker-compose logs -f queue"
log_info "  Stop containers:  docker-compose down"
log_info "  View container resources: docker stats"
log_info ""

log_info "If you encounter issues, run:"
log_info "  docker-compose ps              # Check container status"
log_info "  docker-compose logs apache     # Check Apache errors"
log_info "  docker-compose logs database   # Check MySQL errors"
log_info "  docker-compose logs queue      # Check RabbitMQ errors"
log_info ""
