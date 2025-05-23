#!/bin/bash

# Pre-commit hook for SSH Manager
# This script runs Laravel Pint and Pest tests before allowing commits

echo "🔍 Running pre-commit checks..."

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "❌ vendor directory not found. Please run 'composer install' first."
    exit 1
fi

# Run Laravel Pint formatting check
echo "🎨 Checking code formatting with Laravel Pint..."
if ! ./vendor/bin/pint --test; then
    echo "❌ Code formatting issues found. Run './vendor/bin/pint' to fix them."
    exit 1
fi

# Run tests
echo "🧪 Running tests with Pest..."
if ! ./vendor/bin/pest --no-coverage; then
    echo "❌ Tests failed. Please fix failing tests before committing."
    exit 1
fi

echo "✅ All pre-commit checks passed!"
exit 0