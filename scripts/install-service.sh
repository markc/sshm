#!/bin/bash

# Install SSHM FrankenPHP systemd service
# Run as: ./scripts/install-service.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
SERVICE_NAME="sshm-frankenphp"
SERVICE_FILE="$SCRIPT_DIR/$SERVICE_NAME.service"
SYSTEMD_DIR="/etc/systemd/system"

echo "🚀 Installing SSHM FrankenPHP systemd service..."

# Check if running as root or with sudo
if [[ $EUID -eq 0 ]]; then
    echo "❌ Please run this script as your regular user, not as root"
    echo "   The script will use sudo when needed"
    exit 1
fi

# Verify FrankenPHP is installed
if ! command -v frankenphp &> /dev/null; then
    echo "❌ FrankenPHP not found. Please install it first:"
    echo "   https://frankenphp.dev/docs/install/"
    exit 1
fi

# Verify project structure
if [[ ! -f "$PROJECT_DIR/Caddyfile" ]]; then
    echo "❌ Caddyfile not found in $PROJECT_DIR"
    exit 1
fi

if [[ ! -f "$PROJECT_DIR/artisan" ]]; then
    echo "❌ Laravel project not found in $PROJECT_DIR"
    exit 1
fi

# Stop FrankenPHP if running manually
echo "🛑 Stopping any running FrankenPHP processes..."
pkill -f frankenphp || true
sleep 2

# Copy service file to systemd
echo "📋 Installing systemd service file..."
sudo cp "$SERVICE_FILE" "$SYSTEMD_DIR/$SERVICE_NAME.service"
sudo chown root:root "$SYSTEMD_DIR/$SERVICE_NAME.service"
sudo chmod 644 "$SYSTEMD_DIR/$SERVICE_NAME.service"

# Reload systemd and enable service
echo "🔄 Reloading systemd daemon..."
sudo systemctl daemon-reload

echo "✅ Enabling $SERVICE_NAME service..."
sudo systemctl enable "$SERVICE_NAME"

# Set proper permissions for storage and cache
echo "🔐 Setting proper permissions..."
chmod -R 775 "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"

# Start the service
echo "🚀 Starting $SERVICE_NAME service..."
sudo systemctl start "$SERVICE_NAME"

# Wait a moment for service to start
sleep 3

# Check service status
echo ""
echo "📊 Service Status:"
sudo systemctl status "$SERVICE_NAME" --no-pager -l

echo ""
echo "✅ SSHM FrankenPHP service installed successfully!"
echo ""
echo "🔧 Useful commands:"
echo "   Start:   sudo systemctl start $SERVICE_NAME"
echo "   Stop:    sudo systemctl stop $SERVICE_NAME"
echo "   Restart: sudo systemctl restart $SERVICE_NAME"
echo "   Status:  sudo systemctl status $SERVICE_NAME"
echo "   Logs:    sudo journalctl -u $SERVICE_NAME -f"
echo ""
echo "🌐 Access SSHM at: http://127.0.0.1:8000/admin"
echo ""