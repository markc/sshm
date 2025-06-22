# SSH Manager (SSHM)

[![CI Pipeline](https://github.com/markc/sshm/actions/workflows/ci.yml/badge.svg)](https://github.com/markc/sshm/actions/workflows/ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat&logo=php)](https://php.net)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20.svg?style=flat&logo=laravel)](https://laravel.com)
[![Filament 4](https://img.shields.io/badge/Filament-4.0-FFAA00.svg?style=flat&logo=filament)](https://filamentphp.com)
[![Tests](https://img.shields.io/badge/tests-136%20passed-green.svg?style=flat)](https://github.com/markc/sshm/actions/workflows/ci.yml)

## ⚠️ **CRITICAL SECURITY WARNING** ⚠️
**THIS APPLICATION SHOULD ONLY BE USED ON LOCALHOST (127.0.0.1) AND NEVER ON A PUBLIC OR EXPOSED IP ADDRESS. IT ALLOWS ARBITRARY COMMAND EXECUTION ON REMOTE SERVERS THROUGH A WEB INTERFACE.**

## What is SSH Manager?

SSH Manager (SSHM) is a sophisticated web-based SSH management application built with Laravel 12 and Filament 4.0. It provides a modern, intuitive interface for managing SSH connections, executing remote commands, and administering SSH configurations through your web browser.

## Core Features

- **Real-Time Command Execution**: Execute SSH commands with live streaming output
- **Connection Management**: Store and organize SSH host configurations
- **Key Management**: Generate, import, and manage SSH key pairs
- **Advanced Debugging**: Verbose connection debugging for troubleshooting
- **Dashboard Overview**: System status, statistics, and security reminders
- **Desktop Mode**: Run without authentication on trusted systems

## Quick Start

```bash
# Clone the repository
git clone https://github.com/yourusername/sshm.git
cd sshm

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Set up database
touch database/database.sqlite
php artisan migrate

# Create admin user
php artisan make:filament-user

# Build assets and start server
npm run build
php artisan serve

# Access at: http://localhost:8000/admin
```

## Using SSH Manager

### Dashboard
The dashboard provides immediate visibility into your SSH environment with system versions, host/key statistics, and security reminders.

### SSH Command Runner
Execute commands on remote servers with a split-screen interface:
- **Left side**: Command input area for multi-line scripts
- **Right side**: Host selection and execution options
- **Real-time output**: See results as they stream from the server
- **Debug mode**: Enable verbose logging for troubleshooting
- **Bash mode**: Run commands in interactive bash environment

### SSH Host Management
- Store and organize SSH connection configurations
- Test connectivity to verify host availability
- Sync configurations to SSH config files
- Import existing SSH configurations

### SSH Key Management
- Generate new key pairs (RSA, Ed25519, ECDSA)
- Import existing keys from files
- Deploy keys to filesystem automatically
- View key fingerprints and metadata

### System Administration
- Initialize SSH directory structure with proper permissions
- Control SSH daemon service (start/stop/restart)
- Sync between database and filesystem configurations

## Desktop Mode

For trusted desktop environments, you can run SSHM without authentication:

```bash
# Enable desktop mode
./desktop-mode.sh enable

# Disable desktop mode
./desktop-mode.sh disable

# Check current mode
./desktop-mode.sh status
```

Desktop mode features:
- Auto-login without credentials
- No authentication screen
- Preserves all settings when switching modes
- Ideal for personal workstations

## Configuration

After installation, navigate to **SSH Settings** in the admin panel to configure:
- SSH Home Directory (e.g., `/home/username`)
- Default SSH User
- Default Port (usually 22)
- Default Key Type (ed25519 recommended)
- Host Key Checking

## Security Considerations

- **Network**: Only run on localhost, never expose to public networks
- **Access**: Use strong authentication and consider IP restrictions
- **Commands**: Be aware that users can execute any command on remote servers
- **Keys**: Private keys are stored in the database - ensure proper encryption
- **Privileges**: Use dedicated SSH users with minimal required permissions

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).