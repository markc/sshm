# Testing the Filament SSH Terminal Package

## Overview

This document provides comprehensive instructions for testing the `markc/filament-ssh-terminal` package that was extracted from the SSHM project. The package provides a hybrid SSH terminal emulator widget for Filament v4 with real-time streaming and zero FOUC.

**Repository**: https://github.com/markc/filament-ssh-terminal

## Package Background

This package was extracted from the SSH Manager (SSHM) project to benefit the broader Filament community. It preserves all the performance optimizations and hybrid architecture that were developed to eliminate Livewire morphing conflicts and achieve ultra-fast terminal rendering.

### Key Features Extracted:
- 🚀 Hybrid architecture: Livewire forms + Pure JavaScript terminal
- ⚡ Zero FOUC with GPU acceleration and CSS containment
- 📡 Real-time SSH streaming via Server-Sent Events
- 🖥️ Classic 80x25 terminal emulator with Mac-style design
- 🐛 Advanced debugging with performance metrics
- 🔒 Security-focused with configurable options

## Testing Methods

### Method 1: Install from GitHub (Recommended)

This method tests the package as it would be used by other developers.

#### Step 1: Create Test Project

```bash
# Create a fresh Laravel project
composer create-project laravel/laravel filament-ssh-test
cd filament-ssh-test

# Install Filament v4
composer require filament/filament:"^4.0-beta"
php artisan filament:install --panels
```

#### Step 2: Install the Package

```bash
# Install directly from GitHub
composer require markc/filament-ssh-terminal:dev-main

# Publish package assets
php artisan vendor:publish --tag="filament-ssh-terminal-migrations"
php artisan vendor:publish --tag="filament-ssh-terminal-config"

# Run migrations
php artisan migrate
```

#### Step 3: Configure Filament Panel

Edit `app/Providers/Filament/AdminPanelProvider.php`:

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

// Add this import
use Markc\FilamentSshTerminal\FilamentSshTerminalPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin')
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
            ])
            // Register the SSH Terminal plugin
            ->plugins([
                FilamentSshTerminalPlugin::make(),
            ]);
    }
}
```

#### Step 4: Add Widget to Dashboard

Create or edit `app/Filament/Pages/Dashboard.php`:

```php
<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Markc\FilamentSshTerminal\Widgets\SshTerminalWidget;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            SshTerminalWidget::class,
        ];
    }
}
```

### Method 2: Local Development with Symlinks

For faster development and testing cycles:

#### Step 1: Configure Local Repository

In your test project's `composer.json`, add a local repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/tmp/filament-ssh-terminal"
        }
    ],
    "require": {
        "markc/filament-ssh-terminal": "*"
    }
}
```

#### Step 2: Install with Symlink

```bash
composer install
```

This creates a symlink to your local package for immediate testing of changes.

### Method 3: Composer Private Repository

For team testing:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/markc/filament-ssh-terminal"
        }
    ]
}
```

## Configuration Options

### Basic SSH Host Configuration

Edit `config/filament-ssh-terminal.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSH Connection Settings
    |--------------------------------------------------------------------------
    */
    
    'timeout' => env('SSH_TIMEOUT', 1800), // 30 minutes
    'port' => env('SSH_DEFAULT_PORT', 22),
    'user' => env('SSH_DEFAULT_USER', 'root'),
    'strict_host_checking' => env('SSH_STRICT_HOST_CHECKING', false),
    
    /*
    |--------------------------------------------------------------------------
    | Predefined SSH Hosts
    |--------------------------------------------------------------------------
    */
    
    'hosts' => [
        'localhost' => [
            'host' => '127.0.0.1',
            'port' => 22,
            'user' => 'root',
            'private_key_path' => '/home/user/.ssh/id_rsa',
        ],
        'test-server' => [
            'host' => 'example.com',
            'port' => 22,
            'user' => 'deploy',
            'password' => 'secure-password', // Not recommended for production
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Terminal Configuration
    |--------------------------------------------------------------------------
    */
    
    'terminal' => [
        'width' => 80,
        'height' => 25,
        'theme' => 'classic', // classic, modern, minimal
        'auto_scroll' => true,
        'show_timestamps' => false,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    
    'security' => [
        'enable_bash_mode' => true,
        'enable_debug_mode' => true,
        'log_commands' => true,
        'max_command_length' => 2048,
    ],
];
```

### Database-Driven SSH Connections

Use the included model for dynamic SSH host management:

```php
<?php

use Markc\FilamentSshTerminal\Models\SshConnection;

// Create SSH connections programmatically
SshConnection::create([
    'name' => 'production-server',
    'host' => '192.168.1.100',
    'port' => 22,
    'username' => 'root',
    'private_key_path' => '/home/user/.ssh/id_rsa',
    'active' => true,
    'description' => 'Production web server',
]);

SshConnection::create([
    'name' => 'staging-server',
    'host' => '192.168.1.101', 
    'port' => 2222,
    'username' => 'deploy',
    'password' => 'staging-password',
    'active' => true,
    'description' => 'Staging environment',
]);
```

### Environment Variables

Add to your `.env` file:

```env
# SSH Terminal Configuration
SSH_TIMEOUT=1800
SSH_DEFAULT_PORT=22
SSH_DEFAULT_USER=root
SSH_STRICT_HOST_CHECKING=false

# Security Settings
SSH_ENABLE_BASH_MODE=true
SSH_ENABLE_DEBUG_MODE=true
SSH_LOG_COMMANDS=true
```

## Testing Scenarios

### Scenario 1: Safe Testing (Recommended)

Test the UI without real SSH connections:

```php
// config/filament-ssh-terminal.php
'hosts' => [
    'mock-host' => [
        'host' => 'nonexistent.example.com',
        'port' => 22,
        'user' => 'test',
        'private_key_path' => '/nonexistent/key',
    ],
],

'security' => [
    'enable_bash_mode' => false,
    'enable_debug_mode' => true,
    'log_commands' => true,
],
```

**Expected Results:**
- ✅ Widget loads correctly
- ✅ Terminal interface renders
- ✅ Form controls work
- ⚠️ SSH connections fail gracefully
- ✅ Error messages display properly

### Scenario 2: Local SSH Testing

Test with local SSH (if available):

```php
'hosts' => [
    'localhost' => [
        'host' => '127.0.0.1',
        'port' => 22,
        'user' => 'your-username',
        'private_key_path' => '/home/your-username/.ssh/id_rsa',
    ],
],
```

**Test Commands:**
- `whoami`
- `pwd` 
- `ls -la`
- `free -h`
- `df -h`

### Scenario 3: Docker Container Testing

Create a test SSH container:

```bash
# Run a test SSH container
docker run -d --name ssh-test \
  -p 2222:22 \
  -e SSH_ENABLE_PASSWORD_AUTH=true \
  -e SSH_USERS="test:1000:1000" \
  -e SSH_USER_test_PASS=testpass \
  lscr.io/linuxserver/openssh-server:latest
```

```php
'hosts' => [
    'docker-test' => [
        'host' => '127.0.0.1',
        'port' => 2222,
        'user' => 'test',
        'password' => 'testpass',
    ],
],
```

## Testing Checklist

### UI Components
- [ ] SSH Terminal widget appears on dashboard
- [ ] Command textarea accepts input
- [ ] SSH host selector shows configured hosts
- [ ] Run Command button responds to clicks
- [ ] Debug toggle shows/hides debug information
- [ ] Bash Mode toggle functions correctly

### Terminal Functionality
- [ ] Terminal displays with classic 80x25 design
- [ ] Mac-style window header appears
- [ ] Terminal buttons (close/minimize/maximize) render
- [ ] Terminal area has proper scrolling
- [ ] Green text on black background displays correctly

### Streaming & Performance
- [ ] Commands execute without page refresh
- [ ] Real-time output appears in terminal
- [ ] Debug information shows connection metrics
- [ ] Performance timings display (connection, execution, total)
- [ ] No Flash of Unstyled Content (FOUC) occurs
- [ ] Terminal content persists during Livewire updates

### Error Handling
- [ ] Invalid host configurations show appropriate errors
- [ ] Connection timeouts handled gracefully
- [ ] SSH authentication failures display proper messages
- [ ] Network errors don't crash the interface
- [ ] Debug mode shows detailed error information

### Security Features
- [ ] Commands are logged when enabled
- [ ] Max command length restriction works
- [ ] Bash mode can be disabled
- [ ] Debug mode can be toggled off
- [ ] Sensitive information properly hidden

## Quick Setup Commands

```bash
# Complete test setup sequence
composer create-project laravel/laravel filament-ssh-test
cd filament-ssh-test
composer require filament/filament:"^4.0-beta"
php artisan filament:install --panels
composer require markc/filament-ssh-terminal:dev-main
php artisan vendor:publish --tag="filament-ssh-terminal-migrations"
php artisan vendor:publish --tag="filament-ssh-terminal-config"
php artisan migrate
php artisan make:filament-user
php artisan serve
```

Visit: http://localhost:8000/admin

## Troubleshooting

### Common Issues

1. **Widget doesn't appear:**
   - Check plugin registration in AdminPanelProvider
   - Verify widget is added to Dashboard getWidgets()
   - Clear config cache: `php artisan config:clear`

2. **CSS/JS not loading:**
   - Clear view cache: `php artisan view:clear`
   - Check asset publishing: `php artisan vendor:publish --tag="filament-ssh-terminal-assets"`
   - Verify Vite is running if using local development

3. **SSH connections fail:**
   - Check host configuration in config file
   - Verify SSH credentials and permissions
   - Enable debug mode to see detailed connection info
   - Check Laravel logs: `tail -f storage/logs/laravel.log`

4. **Livewire conflicts:**
   - The package should handle Livewire morphing automatically
   - If issues persist, check JavaScript console for errors
   - Verify `wire:ignore` directives are preserved

### Debug Information

Enable comprehensive debugging:

```php
'security' => [
    'enable_debug_mode' => true,
    'log_commands' => true,
],
```

Check logs for SSH connection details:
```bash
tail -f storage/logs/laravel.log | grep SSH
```

## Performance Verification

The package should demonstrate:

1. **Zero FOUC**: No flash of unstyled content during terminal updates
2. **Smooth animations**: Fade-in effects work without jank
3. **Fast rendering**: Terminal output appears immediately
4. **GPU acceleration**: Smooth scrolling in terminal area
5. **Efficient updates**: Only terminal content updates, forms remain stable

## Next Steps

After successful testing:

1. **Create release tags** for stable versions
2. **Submit to Packagist** for public composer installation
3. **Share with Filament community** on Discord/forums
4. **Document advanced usage patterns** for complex scenarios
5. **Collect feedback** from other developers

## Contributing

If you find issues during testing:

1. Create GitHub issues with detailed reproduction steps
2. Submit pull requests for improvements
3. Share usage examples and edge cases
4. Help improve documentation

This package represents months of optimization work from the SSHM project and should provide a solid foundation for SSH terminal functionality in any Filament application.