# SSH Manager (SSHM)

[![CI Pipeline](https://github.com/markc/sshm/actions/workflows/ci.yml/badge.svg)](https://github.com/markc/sshm/actions/workflows/ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat&logo=php)](https://php.net)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20.svg?style=flat&logo=laravel)](https://laravel.com)
[![Filament 4](https://img.shields.io/badge/Filament-4.0-FFAA00.svg?style=flat&logo=filament)](https://filamentphp.com)

**THIS APPLICATION SHOULD ONLY BE USED ON LOCALHOST (127.0.0.1) AND NEVER ON A PUBLIC OR EXPOSED IP ADDRESS. IT ALLOWS ARBITRARY COMMAND EXECUTION ON REMOTE SERVERS THROUGH A WEB INTERFACE.**

*This project was entirely "vibe" coded with the help of Claude Code - collaborative development at its finest! 🤖*

A modern web-based SSH management application built with Laravel 12 and Filament 4.0. Execute remote commands, manage SSH hosts and keys, all through an intuitive web interface.

## Features

- **Real-time command execution** with streaming output
- **SSH host management** with connection testing
- **SSH key generation** and deployment
- **Advanced debugging** and performance timing
- **Desktop mode** for trusted environments

## Quick Start

```bash
git clone https://github.com/yourusername/sshm.git && cd sshm
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan make:filament-user
npm run build && php artisan serve
```

Access at: http://localhost:8000/admin

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