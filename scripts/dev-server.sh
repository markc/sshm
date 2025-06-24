#!/bin/bash

# Quick development server startup for SSHM
# This is the simplest way to start SSHM for development

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "🚀 Starting SSHM Development Server"
echo "====================================="

# Change to project directory
cd "$PROJECT_DIR"

# Stop any existing FrankenPHP processes
echo "🛑 Stopping existing processes..."
pkill -f frankenphp 2>/dev/null || true
sleep 1

# Check dependencies
echo "🔍 Checking dependencies..."
if ! command -v frankenphp &> /dev/null; then
    echo "❌ FrankenPHP not found!"
    echo "   Install from: https://frankenphp.dev/docs/install/"
    exit 1
fi

if [[ ! -f "Caddyfile" ]]; then
    echo "❌ Caddyfile not found!"
    exit 1
fi

if [[ ! -f ".env" ]]; then
    echo "❌ .env file not found!"
    echo "   Copy .env.example to .env and configure"
    exit 1
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan cache:clear >/dev/null 2>&1 || true
php artisan config:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

# Check if port is available
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "❌ Port 8000 is already in use"
    echo "   Kill existing processes: pkill -f frankenphp"
    exit 1
fi

# Start server
echo "🚀 Starting FrankenPHP server..."
echo "📍 Project: $(pwd)"
echo "🌐 URL: http://127.0.0.1:8000/admin"
echo "🛑 Press Ctrl+C to stop"
echo ""

# Start FrankenPHP with the Caddyfile
exec frankenphp run --config Caddyfile