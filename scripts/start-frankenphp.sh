#!/bin/bash

# SSHM FrankenPHP Startup Script
# High-performance Laravel application server with persistent memory and SSH connection pooling

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting SSHM with FrankenPHP...${NC}"

# Check if FrankenPHP is installed
if ! command -v frankenphp &> /dev/null; then
    echo -e "${YELLOW}📦 FrankenPHP not found. Downloading...${NC}"
    
    # Download FrankenPHP binary for Linux
    if command -v curl &> /dev/null; then
        echo -e "${BLUE}Downloading FrankenPHP binary...${NC}"
        curl -L https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-x86_64 -o frankenphp
        chmod +x frankenphp
        echo -e "${GREEN}✅ FrankenPHP downloaded successfully${NC}"
    else
        echo -e "${RED}❌ curl is required to download FrankenPHP${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✅ FrankenPHP is already installed${NC}"
fi

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}📦 Installing PHP dependencies...${NC}"
    composer install --optimize-autoloader --no-dev
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚙️ Creating .env file from example...${NC}"
    cp .env.example .env
    php artisan key:generate
fi

# Run Laravel optimizations
echo -e "${YELLOW}⚡ Optimizing Laravel application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create database if it doesn't exist (SQLite)
if [ ! -f "database/database.sqlite" ]; then
    echo -e "${YELLOW}🗄️ Creating database...${NC}"
    touch database/database.sqlite
    php artisan migrate --force
fi

# Clear any existing caches for fresh start
echo -e "${YELLOW}🧹 Clearing caches for fresh start...${NC}"
php artisan cache:clear

# Start FrankenPHP with optimized settings
echo -e "${GREEN}✅ Starting FrankenPHP server...${NC}"
echo -e "${BLUE}📡 Application will be available at: http://localhost:8000${NC}"
echo -e "${BLUE}🔧 Admin panel: http://localhost:8000/admin${NC}"
echo -e "${YELLOW}💡 SSH commands will use Server-Sent Events for optimal performance${NC}"
echo -e "${YELLOW}🔄 Connection pooling and multiplexing enabled${NC}"
echo ""

# FrankenPHP configuration
export FRANKENPHP_CONFIG="
{
    apps: {
        sshm: {
            root: $(pwd)/public,
            index: index.php,
            try_files: [\$uri, /index.php],
            worker: {
                file: $(pwd)/worker.php,
                watch: false
            }
        }
    },
    http: {
        servers: {
            main: {
                listen: [\":8000\"],
                routes: [{
                    match: [{host: [\"localhost\", \"127.0.0.1\"]}],
                    handle: [{
                        handler: \"frankenphp\",
                        app: \"sshm\"
                    }]
                }]
            }
        }
    }
}"

# Determine which FrankenPHP binary to use
FRANKENPHP_CMD="frankenphp"
if [ -f "./frankenphp" ]; then
    FRANKENPHP_CMD="./frankenphp"
fi

# Start FrankenPHP with optimized settings
echo -e "${GREEN}🔥 Starting FrankenPHP server...${NC}"
exec $FRANKENPHP_CMD run