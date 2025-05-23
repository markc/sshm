# SSH Manager (SSHM)

SSH Manager is a Laravel 12 + Filament 3.3 web application that allows you to execute SSH commands on remote servers and manage your SSH configurations directly from a web interface.

## Features

- **SSH Command Runner**: Execute SSH commands on remote servers
- **SSH Host Management**: Manage your SSH host configurations
- **SSH Key Management**: Generate, import, and manage SSH keys
- **Directory Initialization**: Set up the proper SSH directory structure
- **Permission Management**: Ensure correct file permissions for SSH files
- **SSH Service Control**: Start/stop the SSH service on the local machine

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- SQLite

### Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/sshm.git
   cd sshm
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Create an environment file and generate an app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Set up the database:
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

5. Create an admin user:
   ```bash
   php artisan make:filament-user
   ```

6. Install and build frontend assets:
   ```bash
   npm install
   npm run build
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

8. Visit `http://localhost:8000/admin` to access the Filament admin panel.

## Configuration

SSH settings can be configured in two ways:

1. **Using the Settings Page (Recommended)**:
   - Log in to the admin panel
   - Navigate to "SSH Settings" in the sidebar
   - Set your home directory (e.g., `/home/markc`) and other SSH options
   - Click "Save Settings"

2. **Using Environment Variables**:
   Edit the `.env` file to configure SSH settings:

   ```
   # SSH Configuration
   SSH_HOME_DIR="/home/user"  # Change to your home directory
   SSH_DEFAULT_USER="root"
   SSH_DEFAULT_PORT=22
   SSH_DEFAULT_KEY_TYPE="ed25519"
   SSH_STRICT_HOST_CHECKING=false
   ```

The settings page overrides the environment variables once values are saved.

## Usage

### SSH Command Runner

1. Navigate to the "SSH Command Runner" page in the admin panel
2. Select a saved SSH host or enter custom connection details
3. Enter the SSH command(s) to execute
4. Click "Run Command" to execute the command and view the output

### SSH Host Management

1. Navigate to the "SSH Hosts" page
2. Add, edit, or delete SSH host configurations
3. Use actions to test connections, sync configurations to files, etc.

### SSH Key Management

1. Navigate to the "SSH Keys" page
2. Generate new SSH keys or import existing ones
3. View key details and fingerprints
4. Sync keys to the filesystem

### SSH Directory Initialization

1. Navigate to the "SSH Hosts" page
2. Click "Initialize SSH Directory" in the top actions
3. This will set up the proper SSH directory structure

### Permission Management

1. Navigate to the "SSH Hosts" page
2. Click "Update SSH Permissions" in the top actions
3. This will ensure correct file permissions for SSH files

### SSH Service Control

1. Navigate to the "SSH Hosts" page
2. Click "SSH Service Control" in the top actions
3. Choose to start/enable or stop/disable the SSH service

## Security Considerations

This application allows for arbitrary command execution on remote servers. Use with caution and consider the following security measures:

1. Restrict access to the application to trusted users only
2. Use strong passwords for the admin account
3. Use key-based authentication instead of passwords
4. Run SSH commands with a user with limited privileges
5. Consider implementing input validation or command whitelisting

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).# sshm
