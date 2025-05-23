# SSH Manager (SSHM)

## ⚠️ **CRITICAL SECURITY WARNING** ⚠️
**THIS APPLICATION SHOULD ONLY BE USED ON LOCALHOST (127.0.0.1) AND NEVER ON A PUBLIC OR EXPOSED IP ADDRESS. IT ALLOWS ARBITRARY COMMAND EXECUTION ON REMOTE SERVERS THROUGH A WEB INTERFACE.**

## What is SSH Manager?

SSH Manager (SSHM) is a sophisticated web-based SSH management application built with Laravel 12 and Filament 3.3. It provides a modern, intuitive interface for managing SSH connections, executing remote commands, and administering SSH configurations through your web browser.

## Core Functionality

SSHM transforms command-line SSH operations into a user-friendly web interface, offering:

**Command Execution Engine**: Execute SSH commands on remote servers with real-time streaming output, allowing you to see command results as they happen rather than waiting for completion. The interface supports both simple commands and complex multi-line scripts.

**Connection Management**: Store and organize SSH host configurations with support for custom ports, usernames, and SSH key authentication. Switch between saved hosts or use custom connection details on-the-fly.

**Key Management System**: Generate, import, and manage SSH key pairs with support for multiple key types (RSA, Ed25519, ECDSA). Keys are stored securely and can be synced to the filesystem automatically.

**Advanced Debugging**: Built-in verbose debugging system that shows detailed connection information, authentication steps, and command execution flow for troubleshooting SSH issues.

**Dashboard Overview**: Real-time system status showing SSH host counts, key statistics, package versions, and security reminders all in one centralized view.

## Installation

**Quick Start Tip**: For a complete PHP development environment, visit [php.new](https://php.new) which provides PHP, Laravel, and Composer in a ready-to-use package.

<pre>
# Clone the repository
git clone https://github.com/yourusername/sshm.git
cd sshm

# Install PHP dependencies
composer install

# Create environment file and generate app key
cp .env.example .env
php artisan key:generate

# Set up database
touch database/database.sqlite
php artisan migrate

# Create admin user (follow prompts)
php artisan make:filament-user

# Install and build frontend assets
npm install
npm run build

# Start development server
php artisan serve

# Access admin panel at: http://localhost:8000/admin
</pre>

## Detailed Installation Steps

### Prerequisites
- **PHP 8.2+** with extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath
- **Composer** for PHP dependency management
- **Node.js 18+** and **NPM** for frontend asset compilation
- **SQLite** database (included in most PHP installations)

### Step-by-Step Installation

1. **Repository Setup**: Clone the SSHM repository and navigate to the project directory. This downloads all source code and configuration files.

2. **PHP Dependencies**: Run `composer install` to download and install all PHP packages including Laravel framework, Filament admin panel, and the Spatie SSH library for remote command execution.

3. **Environment Configuration**: Copy the example environment file and generate a unique application key for encryption and security. This key protects session data and other sensitive information.

4. **Database Initialization**: Create an SQLite database file and run migrations to set up tables for SSH hosts, keys, settings, and user management.

5. **Admin User Creation**: Use the Filament command to create your first administrator account. You'll be prompted for name, email, and password.

6. **Frontend Assets**: Install Node.js dependencies and compile CSS/JavaScript assets using Vite. This builds the modern admin interface.

7. **Development Server**: Start Laravel's built-in development server on `localhost:8000`. The admin panel is accessible at `/admin`.

### Post-Installation Configuration

Navigate to the **SSH Settings** page in the admin panel to configure:
- **SSH Home Directory**: Set to your user's home directory (e.g., `/home/username`)
- **Default SSH User**: Usually `root` or your username for remote connections
- **Default Port**: Standard SSH port is 22
- **Key Type**: Recommended to use `ed25519` for new keys
- **Host Key Checking**: Disable for testing, enable for production security

## Using SSH Manager

### Dashboard Overview
The dashboard provides immediate visibility into your SSH environment:
- **System Versions**: Shows current Laravel, Filament, and Spatie SSH package versions
- **SSH Statistics**: Real-time counts of configured hosts and managed keys with active/inactive status
- **Security Reminders**: Important security considerations and best practices

### SSH Command Runner
The heart of SSHM is the command execution interface:

**Layout**: Split-screen design with command input on the left (50%) and controls on the right (50%)

**Command Input**: Large textarea supporting multi-line commands, scripts, and complex operations

**Host Selection**: Choose from saved SSH hosts or enter custom connection details (hostname, port, username, key file)

**Execution Options**:
- **Verbose Debug**: Enable detailed connection and execution logging
- **Use Bash**: Wrap commands in interactive bash (`bash -ci`) for environment variable and alias access

**Real-Time Output**: Commands stream results live with auto-scrolling terminal-style display

**Connection Modes**:
1. **Saved Hosts**: Select from pre-configured SSH connections
2. **Custom Connection**: Enter connection details on-demand for one-time use

### SSH Host Management
Organize and maintain your SSH connections:

**Host Configuration**: Store hostname, port, username, SSH key assignments, and connection notes

**Bulk Operations**: 
- Test connectivity to multiple hosts
- Sync configurations to SSH config files
- Import existing SSH configurations
- Initialize SSH directory structures

**Connection Testing**: Built-in connectivity verification with detailed error reporting

### SSH Key Management
Comprehensive SSH key lifecycle management:

**Key Generation**: Create new key pairs with selectable algorithms (RSA, Ed25519, ECDSA)

**Key Import**: Import existing private/public key pairs from files or clipboard

**Key Deployment**: Automatically sync keys to filesystem locations for SSH client use

**Security Features**: View key fingerprints, manage key passphrases, and track key usage

### System Administration
Administrative tools for SSH environment management:

**Directory Initialization**: Set up proper SSH directory structure (`~/.ssh/`) with correct permissions

**Permission Management**: Ensure SSH files have secure permissions (700 for directories, 600 for private keys)

**Service Control**: Start/stop/restart SSH daemon service on the local system

**Configuration Sync**: Bidirectional sync between database storage and SSH configuration files

## Security Considerations

**CRITICAL**: SSHM allows arbitrary command execution on remote servers. This is extremely powerful but potentially dangerous.

**Network Security**: Only run on localhost ( 127.0.0.1). Never expose to public networks or untrusted users.

**Access Control**: Protect with strong authentication. Consider implementing IP restrictions and session timeouts.

**Command Auditing**: All executed commands can be logged. Consider implementing command approval workflows for sensitive environments.

**Key Security**: SSH private keys are stored in the database. Ensure database encryption and regular backups.

**User Privileges**: Use dedicated SSH users with minimal required privileges rather than root access.

**Input Validation**: Be cautious with special characters and command injection. Consider implementing command whitelisting for production use.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
