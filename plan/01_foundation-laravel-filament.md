# Stage 1: Laravel 12 + Filament v4 Foundation

## Overview
This stage establishes the foundational Laravel 12 application with Filament v4 admin panel, creating the basic structure for the SSH Manager (SSHM) project. This is the exact foundation used in the original SSHM project.

## Prerequisites
- **PHP 8.4+** with extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Redis
- **Composer 2.x** for dependency management
- **Node.js 18+** and **NPM** for frontend asset compilation
- **Git** for version control
- **SQLite** support (usually included with PHP)

## Step 1: Create Laravel 12 Project

```bash
# Create new Laravel project
composer create-project laravel/laravel sshm
cd sshm

# Verify Laravel version (should be 12.x)
php artisan --version
```

## Step 2: Install Filament v4

```bash
# Install Filament v4
composer require filament/filament:"^4.0"

# Install Filament panel
php artisan filament:install --panels

# Create admin user
php artisan make:filament-user
```

## Step 3: Environment Configuration

Update `.env` file with basic configuration:

```env
APP_NAME=SSHM
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4

# Database - Use SQLite for development
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Session and Cache (file for now, Redis later)
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file

# Basic SSH configuration (will be enhanced later)
SSH_HOME_DIR="/home/markc"
SSH_DEFAULT_USER="root"
SSH_DEFAULT_PORT=22
SSH_DEFAULT_KEY_TYPE="ed25519"
SSH_STRICT_HOST_CHECKING=false
SSH_TIMEOUT=300
```

## Step 4: Database Setup

```bash
# Create SQLite database
touch database/database.sqlite

# Run initial migrations
php artisan migrate

# Verify admin user creation
php artisan tinker
# >>> User::count() # Should show 1
# >>> exit
```

## Step 5: Basic Project Structure

Create the initial CLAUDE.md file with development guidelines:

```markdown
# SSH Manager (SSHM) - Claude AI Development Instructions

This project was entirely "vibe" coded with the help of Claude Code - collaborative development at its finest! 🤖

## Project Purpose
A modern web-based SSH management application built with Laravel 12 and Filament 4.0. Execute remote commands, manage SSH hosts and keys, all through an intuitive web interface.

## Development Guidelines
- Follow Laravel best practices
- Use Filament v4 conventions
- Maintain security-first approach
- Document all major changes
```

## Step 6: Git Repository Setup

```bash
# Initialize git repository
git init
git add .
git commit -m "first commit"

# Add remote (replace with your repository)
git remote add origin https://github.com/yourusername/sshm.git
git branch -M main
git push -u origin main
```

## Step 7: Test Installation

```bash
# Start development server
php artisan serve

# Access admin panel at: http://localhost:8000/admin
# Login with the admin user created earlier
```

## Step 8: Install Additional Dependencies

```bash
# Install core SSH package
composer require spatie/ssh:"^1.13"

# Install Redis client (for performance optimizations in later stages)
composer require predis/predis:"^2.2"

# Install development tools
composer require pestphp/pest:"^3.8" --dev
composer require laravel/pint:"^1.22" --dev
composer require orchestra/testbench:">=10.4" --dev

# Initialize Pest testing framework
php artisan pest:install
```

## Step 9: Configure Filament Admin Panel

The default `app/Providers/Filament/AdminPanelProvider.php` should be updated to use Amber as the primary color (this matches the final SSHM design). If needed, update it:

```php
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

## Step 10: NPM Dependencies and Asset Compilation

```bash
# Install NPM dependencies
npm install

# Build assets
npm run build

# For development with hot reload
npm run dev
```

## Step 11: Verify Complete Setup

```bash
# Test the application
php artisan test

# Format code
./vendor/bin/pint

# Verify everything works
php artisan serve
```

Visit `http://localhost:8000/admin` and confirm:
- ✅ Filament admin panel loads
- ✅ User authentication works
- ✅ Dashboard displays properly
- ✅ No console errors

## Commit This Stage

```bash
git add .
git commit -m "feat: complete Laravel 12 + Filament v4 foundation setup

- Initialize Laravel 12 project with all dependencies
- Install and configure Filament v4 admin panel with Amber theme
- Set up SQLite database for development
- Install Spatie SSH package for remote command execution
- Configure Pest testing framework with 3 sample tests
- Install Laravel Pint for consistent code formatting
- Add Redis client (Predis) for future performance optimizations
- Configure comprehensive environment variables
- Create admin user and verify authentication
- Set up development tools and basic project structure

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"

git push origin main
```

## Expected File Structure

```
sshm/
├── app/
│   ├── Filament/
│   │   ├── Pages/
│   │   ├── Resources/
│   │   └── Widgets/
│   ├── Models/
│   │   └── User.php
│   └── Providers/
│       └── Filament/
│           └── AdminPanelProvider.php
├── config/
├── database/
│   ├── database.sqlite (created)
│   └── migrations/
├── .env (configured)
├── composer.json (with all dependencies)
├── CLAUDE.md (development guidelines)
└── README.md
```

## Key Achievements

✅ **Laravel 12** installed and configured  
✅ **Filament v4** admin panel working  
✅ **SQLite database** ready for development  
✅ **Spatie SSH** package installed  
✅ **Testing framework** (Pest) configured  
✅ **Code formatting** (Pint) ready  
✅ **Admin user** created and functional  
✅ **Development server** running  

## Next Stage
Proceed to `02_ssh-core-features.md` to implement SSH host and key management functionality.

## Troubleshooting

**Issue: Filament admin panel not accessible**
- Verify `php artisan filament:install --panels` was run
- Check admin user was created: `php artisan make:filament-user`
- Ensure APP_KEY is generated: `php artisan key:generate`

**Issue: Database errors**
- Verify database.sqlite file exists: `touch database/database.sqlite`
- Run migrations: `php artisan migrate`
- Check .env DB_CONNECTION=sqlite

**Issue: Asset compilation errors**
- Run `npm install` to install dependencies
- Try `npm run build` for production or `npm run dev` for development
- Clear any old assets: `rm -rf public/build`