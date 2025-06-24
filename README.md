# SSH Manager (SSHM)

[![CI Pipeline](https://github.com/markc/sshm/actions/workflows/ci.yml/badge.svg)](https://github.com/markc/sshm/actions/workflows/ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat&logo=php)](https://php.net)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20.svg?style=flat&logo=laravel)](https://laravel.com)
[![Filament 4](https://img.shields.io/badge/Filament-4.0-FFAA00.svg?style=flat&logo=filament)](https://filamentphp.com)

⚠️ **SECURITY WARNING**: This application should only be used on localhost (127.0.0.1) and never on a public or exposed IP address. It allows arbitrary command execution on remote servers through a web interface.

🚧 **WORK IN PROGRESS**: This project is actively under development and not yet a finished product. Features may be incomplete or subject to change.

*This project was entirely "vibe" coded with the help of Claude Code - collaborative development at its finest! 🤖*

A modern web-based SSH management application built with Laravel 12 and Filament 4.0. Features a hybrid terminal emulator with real-time streaming, zero FOUC, and ultra-fast performance.

![SSH Manager Terminal](public/img/20250624_SSH_Manager_Terminal.jpg)

## Core Features

- **🚀 Hybrid Terminal**: Real-time SSH command execution with zero FOUC
- **⚡ Ultra-Fast Streaming**: Server-Sent Events with GPU acceleration
- **🖥️ Classic Terminal**: Authentic 80x25 terminal emulator design
- **🔧 Host Management**: SSH hosts and keys with connection testing
- **🐛 Advanced Debugging**: Performance metrics and detailed logging
- **🖱️ Desktop Mode**: Authentication-free mode for trusted environments

## Filament SSH Terminal Plugin

This project has spawned a **standalone Filament plugin** that can be used in any Filament application:

**📦 [filament-ssh-terminal](https://github.com/markc/filament-ssh-terminal)** - Reusable hybrid SSH terminal widget

```bash
composer require markc/filament-ssh-terminal
```

The plugin preserves all performance optimizations from this project and eliminates the common Livewire morphing conflicts that plague SSH terminals.

## Quick Start

```bash
git clone https://github.com/yourusername/sshm.git && cd sshm
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan make:filament-user
npm run build && ./scripts/dev-server.sh
```

Access at: http://localhost:8000/admin

## Starting SSHM

### Development Mode (Recommended)
```bash
# Simple development server
./scripts/dev-server.sh

# Or use the advanced start script
./scripts/start-sshm.sh dev
```

### Production Mode (Systemd Service)
```bash
# Install systemd service (one-time setup)
./scripts/install-service.sh

# Start production service
./scripts/start-sshm.sh prod

# Other service commands
./scripts/start-sshm.sh status   # Show status
./scripts/start-sshm.sh stop     # Stop service
./scripts/start-sshm.sh restart  # Restart service
./scripts/start-sshm.sh logs     # Follow logs
```

### Manual FrankenPHP
```bash
# If you prefer to start manually
frankenphp run --config Caddyfile
```

## Desktop Mode

Run without authentication on trusted systems:

```bash
./desktop-mode.sh enable    # Enable desktop mode
./desktop-mode.sh disable   # Disable desktop mode
./desktop-mode.sh status    # Check current mode
```

## Configuration

Configure SSH settings in the admin panel:
- SSH Home Directory (e.g., `/home/username`)
- Default SSH User, Port, and Key Type
- Host Key Checking preferences

## Security

- **Network**: Localhost only, never expose publicly
- **Access**: Use strong authentication
- **Commands**: Users can execute any remote command
- **Keys**: Private keys stored in database
- **Privileges**: Use minimal SSH user permissions

## License

MIT License