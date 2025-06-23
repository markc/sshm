# SSH Manager Production Deployment Guide

## Overview
This document outlines the production deployment strategy for the SSH Manager application, incorporating Filament v4 best practices and Laravel deployment standards.

## Pre-Deployment Checklist

### Security Configuration
- [ ] Ensure `APP_ENV=production`
- [ ] Set strong `APP_KEY` 
- [ ] Configure secure database credentials
- [ ] Set up proper user authentication for Filament panels
- [ ] Review SSH settings for production security
- [ ] Configure HTTPS/SSL certificates
- [ ] Set up proper file permissions (755 for directories, 644 for files)

### Environment Variables
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_CONNECTION=mysql  # or your production database
# SSH Manager specific settings
SSH_HOME_DIR=/home/sshmanager
SSH_DEFAULT_USER=deploy
SSH_STRICT_HOST_CHECKING=true
```

## Deployment Scripts

### Primary Deployment Script
```bash
#!/bin/bash
# deploy.sh - Primary deployment script

set -e  # Exit on any error

echo "Starting SSH Manager deployment..."

# 1. Code Deployment
git pull origin main
composer install --optimize-autoloader --no-dev --no-interaction

# 2. Clear existing caches
php artisan down --retry=60 --secret="deployment-secret"
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

# 3. Database Migration
php artisan migrate --force

# 4. Filament Optimization
php artisan filament:optimize
php artisan icons:cache

# 5. Laravel Optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Frontend Assets
npm ci --production
npm run build

# 7. File Permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 8. Application Up
php artisan up

echo "Deployment completed successfully!"
```

### Rollback Script
```bash
#!/bin/bash
# rollback.sh - Emergency rollback script

set -e

echo "Starting rollback procedure..."

# Get previous commit hash
PREVIOUS_COMMIT=$(git rev-parse HEAD~1)

# Rollback code
git reset --hard $PREVIOUS_COMMIT

# Reinstall dependencies for previous version
composer install --optimize-autoloader --no-dev --no-interaction

# Clear and recache
php artisan down --retry=60
php artisan optimize:clear
php artisan filament:optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up

echo "Rollback completed to commit: $PREVIOUS_COMMIT"
```

## CI/CD Integration

### GitHub Actions Production Deployment
```yaml
name: Production Deployment

on:
  push:
    branches: [ main ]
    tags: [ 'v*' ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup SSH Key
      uses: webfactory/ssh-agent@v0.9.0
      with:
        ssh-private-key: ${{ secrets.DEPLOY_SSH_KEY }}
    
    - name: Deploy to Production
      run: |
        ssh -o StrictHostKeyChecking=no ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} '
          cd /var/www/sshmanager &&
          ./deploy.sh
        '
    
    - name: Health Check
      run: |
        sleep 30
        curl -f ${{ secrets.DEPLOY_URL }}/health || exit 1
```

## Server Configuration

### Nginx Configuration
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    root /var/www/sshmanager/public;

    index index.php;

    charset utf-8;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### PHP Configuration
```ini
; /etc/php/8.4/fpm/conf.d/99-sshmanager.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1

; Session security
session.cookie_httponly=1
session.cookie_secure=1
session.use_strict_mode=1
```

## Monitoring and Maintenance

### Health Check Endpoint
Create a health check route in `routes/web.php`:
```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'version' => config('app.version'),
    ]);
});
```

### Log Monitoring
```bash
# Monitor application logs
tail -f storage/logs/laravel.log

# Monitor SSH Manager specific activities
grep "SSH" storage/logs/laravel.log

# Monitor Filament panel access
grep "Filament" storage/logs/laravel.log
```

### Backup Strategy
```bash
#!/bin/bash
# backup.sh - Daily backup script

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/sshmanager"

# Database backup
mysqldump --single-transaction sshmanager > "$BACKUP_DIR/db_$DATE.sql"

# Application backup
tar -czf "$BACKUP_DIR/app_$DATE.tar.gz" \
    --exclude="storage/logs/*" \
    --exclude="node_modules" \
    --exclude=".git" \
    /var/www/sshmanager

# SSH configuration backup
tar -czf "$BACKUP_DIR/ssh_$DATE.tar.gz" /home/sshmanager/.ssh/

# Cleanup old backups (keep 30 days)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

## Performance Optimization

### Production Optimizations
1. **OPcache Configuration**: Enable and configure OPcache properly
2. **Database Optimization**: Use connection pooling and query optimization
3. **Asset Optimization**: Minify and compress CSS/JS assets
4. **CDN Integration**: Serve static assets from CDN
5. **Redis Caching**: Implement Redis for session and cache storage

### SSH Manager Specific
1. **SSH Key Management**: Secure key storage and rotation
2. **Connection Pooling**: Optimize SSH connection reuse
3. **Command Logging**: Implement comprehensive audit logging
4. **Rate Limiting**: Prevent abuse of SSH command execution
5. **Resource Monitoring**: Monitor server resources during SSH operations

## Security Considerations

### SSH Manager Security
- Implement strict SSH key access controls
- Log all SSH command executions
- Restrict SSH command capabilities
- Use principle of least privilege
- Implement session timeouts
- Monitor for suspicious activities

### Application Security
- Regular security updates
- Input validation and sanitization
- CSRF protection
- XSS protection
- SQL injection prevention
- Secure file uploads and downloads

## Troubleshooting

### Common Issues
1. **Cache Permission Issues**: Ensure proper ownership of cache directories
2. **Database Connection**: Verify database credentials and connectivity
3. **SSH Key Issues**: Check SSH key permissions and ownership
4. **Filament Panel Access**: Verify user authentication and authorization
5. **Asset Loading**: Ensure proper asset compilation and serving

### Debug Mode
For production debugging (use carefully):
```bash
# Temporarily enable debug mode
php artisan down
# Set APP_DEBUG=true in .env
php artisan config:clear
php artisan up
# Remember to disable after debugging
```

This production deployment guide ensures a secure, optimized, and maintainable SSH Manager deployment following Filament v4 and Laravel best practices.