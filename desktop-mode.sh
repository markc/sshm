#!/bin/bash

# SSHM Desktop Mode Manager
# This script helps manage desktop mode for the SSH Manager application

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to display usage
usage() {
    echo "Usage: $0 {enable|disable|status}"
    echo "  enable  - Enable desktop mode (no authentication required)"
    echo "  disable - Disable desktop mode (normal authentication)"
    echo "  status  - Show current mode"
    exit 1
}

# Function to check current mode
check_mode() {
    if [ -f .env ]; then
        if grep -q "^DESKTOP_MODE=true" .env 2>/dev/null; then
            echo "enabled"
        else
            echo "disabled"
        fi
    else
        echo "no-env"
    fi
}

# Function to enable desktop mode
enable_desktop_mode() {
    echo -e "${YELLOW}Enabling desktop mode...${NC}"
    
    # Check if .env exists
    if [ ! -f .env ]; then
        echo -e "${RED}Error: .env file not found. Please run setup first.${NC}"
        exit 1
    fi
    
    # Backup current .env
    cp .env .env.backup
    echo -e "${GREEN}✓ Created backup: .env.backup${NC}"
    
    # Copy desktop environment
    cp .env.desktop .env
    
    # Preserve APP_KEY from backup
    APP_KEY=$(grep "^APP_KEY=" .env.backup | cut -d '=' -f 2-)
    if [ -n "$APP_KEY" ]; then
        # Use a different delimiter for sed to avoid issues with special characters
        awk -v key="APP_KEY=$APP_KEY" '/^APP_KEY=/ {print key; next} {print}' .env > .env.tmp && mv .env.tmp .env
        echo -e "${GREEN}✓ Preserved APP_KEY${NC}"
    fi
    
    # Preserve SSH_HOME_DIR if set
    if grep -q "^SSH_HOME_DIR=" .env.backup; then
        SSH_HOME_DIR=$(grep "^SSH_HOME_DIR=" .env.backup | cut -d '=' -f 2-)
        awk -v key="SSH_HOME_DIR=$SSH_HOME_DIR" '/^SSH_HOME_DIR=/ {print key; next} {print}' .env > .env.tmp && mv .env.tmp .env
        echo -e "${GREEN}✓ Preserved SSH_HOME_DIR${NC}"
    fi
    
    # Create desktop user
    php artisan sshm:create-desktop-user
    
    # Clear caches
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    
    echo -e "${GREEN}✓ Desktop mode enabled!${NC}"
    echo -e "${YELLOW}You can now access the application without login at: http://localhost:8000/admin${NC}"
}

# Function to disable desktop mode
disable_desktop_mode() {
    echo -e "${YELLOW}Disabling desktop mode...${NC}"
    
    # Check if backup exists
    if [ ! -f .env.backup ]; then
        echo -e "${RED}Error: No backup found. Cannot restore previous configuration.${NC}"
        exit 1
    fi
    
    # Restore backup
    mv .env.backup .env
    echo -e "${GREEN}✓ Restored previous configuration${NC}"
    
    # Clear caches
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    
    echo -e "${GREEN}✓ Desktop mode disabled!${NC}"
    echo -e "${YELLOW}Normal authentication is now required.${NC}"
}

# Function to show status
show_status() {
    MODE=$(check_mode)
    
    if [ "$MODE" == "enabled" ]; then
        echo -e "${GREEN}Desktop mode is ENABLED${NC}"
        echo "- No authentication required"
        echo "- Auto-login as Desktop User"
        
        # Show desktop user info if exists
        if command -v php &> /dev/null; then
            EMAIL=$(grep "^DESKTOP_USER_EMAIL=" .env 2>/dev/null | cut -d '=' -f 2- | tr -d '"')
            NAME=$(grep "^DESKTOP_USER_NAME=" .env 2>/dev/null | cut -d '=' -f 2- | tr -d '"')
            if [ -n "$EMAIL" ]; then
                echo "- User: $NAME <$EMAIL>"
            fi
        fi
    elif [ "$MODE" == "disabled" ]; then
        echo -e "${YELLOW}Desktop mode is DISABLED${NC}"
        echo "- Normal authentication required"
    else
        echo -e "${RED}Environment not configured${NC}"
        echo "- Run setup-script.sh first"
    fi
}

# Main script logic
case "$1" in
    enable)
        enable_desktop_mode
        ;;
    disable)
        disable_desktop_mode
        ;;
    status)
        show_status
        ;;
    *)
        usage
        ;;
esac