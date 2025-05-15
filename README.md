# Laravel SSH Command Runner with Filament v3

This project is a Laravel 12 application integrated with Filament 3.2 that allows you to execute SSH commands on remote servers through a web interface.

## Features

- Execute SSH commands on remote servers from within a Filament admin panel
- Manage multiple SSH connections with different configurations
- Securely store connection details and credentials
- Display command output in a user-friendly format
- Handle connection errors and command execution failures

## Requirements

- PHP 8.2 or higher
- Laravel 12
- Composer
- Node.js and NPM

## Installation

1. Clone the repository:

```bash
git clone <repository-url>
cd ssh
```

2. Install PHP dependencies:

```bash
composer install
```

3. Copy the environment file:

```bash
cp .env.example .env
```

4. Generate application key:

```bash
php artisan key:generate
```

5. Configure your database in the `.env` file:

```
DB_CONNECTION=sqlite
# Or use MySQL/PostgreSQL if preferred
```

6. Configure SSH settings in the `.env` file (optional):

```
SSH_DEFAULT_HOST=127.0.0.1
SSH_DEFAULT_PORT=22
SSH_DEFAULT_USERNAME=user
SSH_DEFAULT_PRIVATE_KEY_PATH=/path/to/your/private/key
# SSH_DEFAULT_PASSWORD=secure_password
```

7. Run database migrations and seed:

```bash
php artisan migrate --seed
```

8. Install frontend dependencies and build assets:

```bash
npm install
npm run build
```

9. Start the development server:

```bash
composer dev
```

## Usage

1. Access the Filament admin panel at `http://localhost:8000/admin`

2. Login with the default admin account:

    - Email: admin@example.com
    - Password: password

3. Navigate to "SSH Configurations" to set up your SSH connections

4. Go to "SSH Command Runner" to execute commands on your configured servers

## Security Considerations

This application allows executing arbitrary commands on remote servers, which presents significant security risks:

1. **Authentication**: Limit access to the SSH Command Runner to trusted administrators only.

2. **SSH Credentials**: Store private keys securely and prefer key-based authentication over passwords.

3. **User Privileges**: On the remote server, use a user with limited privileges for the SSH connections.

4. **Input Validation**: Although the application allows arbitrary commands, consider implementing restrictions for production environments.

5. **Audit Logging**: For production use, implement logging of all executed commands and their results.

## License

MIT# sshm
