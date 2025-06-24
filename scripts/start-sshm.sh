#!/bin/bash

# Start SSHM FrankenPHP server
# Supports both development and production modes

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
SERVICE_NAME="sshm-frankenphp"

# Function to show usage
show_usage() {
    echo "Usage: $0 [dev|prod|status|stop|restart|logs]"
    echo ""
    echo "Commands:"
    echo "  dev     - Start in development mode (foreground)"
    echo "  prod    - Start as systemd service (production)"
    echo "  status  - Show service status"
    echo "  stop    - Stop the service"
    echo "  restart - Restart the service"
    echo "  logs    - Show live logs"
    echo ""
    echo "Examples:"
    echo "  $0 dev      # Development mode"
    echo "  $0 prod     # Production with systemd"
    echo "  $0 logs     # Follow logs"
}

# Function to start development mode
start_dev() {
    echo "🚀 Starting SSHM in development mode..."
    
    # Stop any existing processes
    pkill -f frankenphp || true
    sleep 1
    
    # Check if port 8000 is available
    if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo "❌ Port 8000 is already in use"
        echo "   Please stop the existing service or use a different port"
        exit 1
    fi
    
    # Start FrankenPHP in foreground
    cd "$PROJECT_DIR"
    echo "📍 Starting from: $(pwd)"
    echo "🌐 Access SSHM at: http://127.0.0.1:8000/admin"
    echo "   Press Ctrl+C to stop"
    echo ""
    
    frankenphp run --config Caddyfile
}

# Function to start production mode
start_prod() {
    echo "🚀 Starting SSHM in production mode..."
    
    # Check if systemd service exists
    if ! systemctl list-unit-files | grep -q "$SERVICE_NAME.service"; then
        echo "❌ Systemd service not installed"
        echo "   Run: ./scripts/install-service.sh"
        exit 1
    fi
    
    # Start the service
    sudo systemctl start "$SERVICE_NAME"
    sleep 2
    
    # Show status
    sudo systemctl status "$SERVICE_NAME" --no-pager -l
    echo ""
    echo "🌐 Access SSHM at: http://127.0.0.1:8000/admin"
}

# Function to show status
show_status() {
    if systemctl list-unit-files | grep -q "$SERVICE_NAME.service"; then
        sudo systemctl status "$SERVICE_NAME" --no-pager -l
    else
        echo "❌ Systemd service not installed"
        echo "   Run: ./scripts/install-service.sh"
        
        # Check for manual processes
        if pgrep -f frankenphp > /dev/null; then
            echo ""
            echo "📍 Found running FrankenPHP processes:"
            pgrep -f frankenphp | while read pid; do
                echo "   PID: $pid - $(ps -p $pid -o cmd --no-headers)"
            done
        else
            echo "   No FrankenPHP processes running"
        fi
    fi
}

# Function to stop service
stop_service() {
    echo "🛑 Stopping SSHM..."
    
    if systemctl list-unit-files | grep -q "$SERVICE_NAME.service"; then
        sudo systemctl stop "$SERVICE_NAME"
        echo "✅ Service stopped"
    fi
    
    # Also kill any manual processes
    if pgrep -f frankenphp > /dev/null; then
        pkill -f frankenphp
        echo "✅ Manual processes stopped"
    fi
}

# Function to restart service
restart_service() {
    echo "🔄 Restarting SSHM..."
    
    if systemctl list-unit-files | grep -q "$SERVICE_NAME.service"; then
        sudo systemctl restart "$SERVICE_NAME"
        sleep 2
        sudo systemctl status "$SERVICE_NAME" --no-pager -l
    else
        echo "❌ Systemd service not installed"
        echo "   Run: ./scripts/install-service.sh"
        exit 1
    fi
}

# Function to show logs
show_logs() {
    if systemctl list-unit-files | grep -q "$SERVICE_NAME.service"; then
        echo "📋 Following SSHM logs (press Ctrl+C to exit):"
        sudo journalctl -u "$SERVICE_NAME" -f
    else
        echo "❌ Systemd service not installed"
        echo "   Run: ./scripts/install-service.sh"
        exit 1
    fi
}

# Main logic
case "${1:-}" in
    "dev")
        start_dev
        ;;
    "prod")
        start_prod
        ;;
    "status")
        show_status
        ;;
    "stop")
        stop_service
        ;;
    "restart")
        restart_service
        ;;
    "logs")
        show_logs
        ;;
    *)
        show_usage
        exit 1
        ;;
esac