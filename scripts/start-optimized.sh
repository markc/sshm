#!/bin/bash

# SSHM Ultra-Optimized Startup Script
# Advanced FrankenPHP + Redis + Performance Monitoring

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting SSHM Ultra-Optimized Performance Mode...${NC}"
echo -e "${PURPLE}======================================================${NC}"
echo

# Kill any existing FrankenPHP processes
echo -e "${YELLOW}🛑 Stopping any existing processes...${NC}"
pkill frankenphp 2>/dev/null || true

# Check Redis status
echo -e "${CYAN}📡 Checking Redis server...${NC}"
if systemctl is-active --quiet redis; then
    echo -e "${GREEN}✅ Redis server is running${NC}"
else
    echo -e "${YELLOW}🔄 Starting Redis server...${NC}"
    sudo systemctl start redis
    sleep 2
fi

# Clear performance caches
echo -e "${YELLOW}🧹 Clearing performance caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Test Redis connection
echo -e "${CYAN}🔍 Testing Redis connection...${NC}"
if php artisan tinker --execute="use Illuminate\Support\Facades\Redis; Redis::ping(); echo 'Redis OK';" 2>/dev/null | grep -q "Redis OK"; then
    echo -e "${GREEN}✅ Redis connection successful${NC}"
else
    echo -e "${RED}❌ Redis connection failed${NC}"
    exit 1
fi

# Check composer dependencies
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}📦 Installing PHP dependencies...${NC}"
    composer install --optimize-autoloader --no-dev
fi

# Optimize Laravel for production performance
echo -e "${YELLOW}⚡ Optimizing Laravel for peak performance...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Database optimizations
echo -e "${YELLOW}🗄️  Optimizing database...${NC}"
if [ ! -f "database/database.sqlite" ]; then
    touch database/database.sqlite
    php artisan migrate --force
else
    # Optimize SQLite
    sqlite3 database/database.sqlite "PRAGMA optimize;"
    sqlite3 database/database.sqlite "VACUUM;"
fi

# Clear Redis caches for fresh start
echo -e "${YELLOW}🔄 Preparing Redis for optimized session...${NC}"
php artisan sshm:monitor --clear > /dev/null 2>&1 || true

echo -e "${GREEN}✅ All optimizations complete!${NC}"
echo
echo -e "${PURPLE}🔥 Performance Features Active:${NC}"
echo -e "${GREEN}   • FrankenPHP Worker Mode with persistent memory${NC}"
echo -e "${GREEN}   • Redis-backed sessions and caching${NC}"
echo -e "${GREEN}   • SSH connection pooling with multiplexing${NC}"
echo -e "${GREEN}   • Intelligent command result caching${NC}"
echo -e "${GREEN}   • Server-Sent Events real-time streaming${NC}"
echo -e "${GREEN}   • SQLite WAL mode with optimized settings${NC}"
echo -e "${GREEN}   • Advanced memory management and monitoring${NC}"

echo
echo -e "${CYAN}📊 Performance Monitoring:${NC}"
echo -e "${BLUE}   Monitor: php artisan sshm:monitor${NC}"
echo -e "${BLUE}   Clear stats: php artisan sshm:monitor --clear${NC}"

echo
echo -e "${YELLOW}🚀 Starting FrankenPHP Ultra-Optimized Server...${NC}"
echo -e "${BLUE}📡 Application: http://localhost:8000${NC}"
echo -e "${BLUE}🔧 Admin Panel: http://localhost:8000/admin${NC}"
echo -e "${PURPLE}💨 Expected Performance: Sub-50ms SSH execution${NC}"
echo

# Start FrankenPHP with advanced worker configuration
exec frankenphp run --config Caddyfile