#!/bin/bash
# SSHM Project Setup Script

# Set variables
APP_NAME="SSHM"
APP_ENV="local"
APP_KEY_COMMAND="php artisan key:generate"
APP_URL="http://localhost:8000"
DB_CONNECTION="sqlite"
DB_DATABASE="database/database.sqlite"

# Step 1: Install Laravel
echo "Installing Laravel 12..."
composer create-project laravel/laravel:^12.0 temp-project
cp -r temp-project/. ./
rm -rf temp-project

# Step 2: Set up SQLite database
echo "Setting up SQLite database..."
touch database/database.sqlite

# Step 3: Install Filament and dependencies
echo "Installing Filament 3.3 and dependencies..."
composer require filament/filament:^3.3 spatie/ssh

# Step 4: Create admin user
echo "Creating admin user..."
php artisan make:filament-user admin@example.com --name="Admin" --password="password123"

# Step 5: Install assets
echo "Installing frontend assets..."
npm install
npm run build

# Step 6: Clean up
echo "Cleaning up..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo "Setup complete! Run 'php artisan serve' to start the development server."