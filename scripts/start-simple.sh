#!/bin/bash

# SSHM Optimized Startup (Without Redis dependency)
set -e

echo "🚀 Starting SSHM Optimized Performance Mode..."
echo "=============================================="

# Kill existing processes
pkill frankenphp 2>/dev/null || true

# Laravel optimizations
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Database setup
if [ ! -f "database/database.sqlite" ]; then
    echo "🗄️  Creating database..."
    touch database/database.sqlite
    php artisan migrate --force
fi

echo "✅ Optimizations complete!"
echo
echo "🔥 Performance Features:"
echo "   • FrankenPHP Worker Mode"
echo "   • SSH Connection Pooling"
echo "   • Server-Sent Events Streaming"
echo "   • SQLite WAL Mode"
echo "   • Optimized Buffering (20ms)"

echo
echo "🚀 Starting FrankenPHP..."
echo "📡 http://localhost:8000"
echo "🔧 http://localhost:8000/admin"
echo

exec frankenphp run --config Caddyfile