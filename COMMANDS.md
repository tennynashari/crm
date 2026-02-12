# FlowCRM - Quick Command Reference

## Service Management

### Start/Stop/Restart Services
```bash
# Nginx
sudo systemctl start nginx
sudo systemctl stop nginx
sudo systemctl restart nginx
sudo systemctl status nginx

# PHP-FPM
sudo systemctl restart php8.3-fpm
sudo systemctl status php8.3-fpm

# PostgreSQL
sudo systemctl restart postgresql
sudo systemctl status postgresql

# Supervisor (Queue Workers)
sudo supervisorctl restart crm-worker:*
sudo supervisorctl status
```

## Laravel Commands

### Cache Management
```bash
cd /var/www/crm/backend

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (WARNING: deletes all data)
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Access database console
php artisan tinker
```

### Maintenance Mode
```bash
# Enable maintenance mode
php artisan down

# Disable maintenance mode
php artisan up

# Enable with secret bypass token
php artisan down --secret="bypass-token"
# Access: https://yourdomain.com/bypass-token
```

## Database Management

### PostgreSQL Commands
```bash
# Connect to database
sudo -u postgres psql
psql -U crm -h localhost -d crm

# Inside psql:
\l              # List databases
\dt             # List tables
\d table_name   # Describe table
\q              # Quit

# Backup database
pg_dump -U crm -h localhost crm > backup.sql

# Restore database
psql -U crm -h localhost crm < backup.sql

# Create automated backup
sudo crontab -e
# Add: 0 2 * * * pg_dump -U crm crm > /backups/crm-$(date +\%Y\%m\%d).sql
```

## Log Viewing

### Real-time Log Monitoring
```bash
# Laravel application logs
tail -f /var/www/crm/backend/storage/logs/laravel.log

# Nginx access logs
tail -f /var/log/nginx/access.log

# Nginx error logs
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.3-fpm.log

# PostgreSQL logs
sudo tail -f /var/log/postgresql/postgresql-16-main.log

# Queue worker logs
tail -f /var/www/crm/backend/storage/logs/worker.log
```

### Search Logs
```bash
# Search for errors in Laravel log
grep -i "error" /var/www/crm/backend/storage/logs/laravel.log

# View last 100 lines
tail -n 100 /var/www/crm/backend/storage/logs/laravel.log

# View specific date
grep "2026-02-12" /var/www/crm/backend/storage/logs/laravel.log
```

## Nginx Configuration

### Test & Reload
```bash
# Test configuration
sudo nginx -t

# Reload (no downtime)
sudo systemctl reload nginx

# Restart (brief downtime)
sudo systemctl restart nginx

# Edit configuration
sudo nano /etc/nginx/sites-available/crm
```

## Permission Fixes

### Fix Laravel Permissions
```bash
cd /var/www/crm/backend

# Fix storage permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Fix all permissions
sudo chown -R www-data:www-data /var/www/crm
sudo find /var/www/crm -type f -exec chmod 644 {} \;
sudo find /var/www/crm -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/crm/backend/storage
sudo chmod -R 775 /var/www/crm/backend/bootstrap/cache
```

## SSL/HTTPS

### Certbot Commands
```bash
# Get new certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Renew certificates
sudo certbot renew

# Test renewal (dry run)
sudo certbot renew --dry-run

# List certificates
sudo certbot certificates

# Revoke certificate
sudo certbot revoke --cert-path /etc/letsencrypt/live/yourdomain.com/cert.pem
```

## Firewall (UFW)

### Basic Commands
```bash
# Check status
sudo ufw status

# Enable/Disable
sudo ufw enable
sudo ufw disable

# Allow/Deny ports
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw deny 3306/tcp

# Delete rule
sudo ufw delete allow 80/tcp

# Reset firewall
sudo ufw reset
```

## System Monitoring

### Resource Usage
```bash
# Disk usage
df -h

# Check specific directory size
du -sh /var/www/crm

# Memory usage
free -m

# CPU and memory
top
htop  # Install: sudo apt install htop

# Process list
ps aux | grep php
ps aux | grep nginx
```

### Network
```bash
# Check open ports
sudo netstat -tulpn

# Check specific port
sudo lsof -i :80

# Test HTTP response
curl -I http://localhost
curl -I https://yourdomain.com
```

## Frontend (Vue.js)

### Build Commands
```bash
cd /var/www/crm/frontend

# Install dependencies
npm install

# Development build
npm run dev

# Production build
npm run build

# Preview production build
npm run preview

# Check for updates
npm outdated

# Update packages
npm update
```

## Deployment Workflow

### Full Deployment (First Time)
```bash
# 1. Upload code to server
scp -r /local/path user@server:/var/www/crm

# 2. Run deployment script
sudo bash /var/www/crm/deploy.sh

# 3. Setup application
sudo bash /var/www/crm/setup-app.sh

# 4. Get SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Update Existing Deployment
```bash
# Option 1: Use update script
bash /var/www/crm/update-app.sh

# Option 2: Manual update
cd /var/www/crm
git pull origin main

# Backend
cd backend
composer install --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Frontend
cd ../frontend
npm install
npm run build

# Restart services
sudo systemctl restart php8.3-fpm nginx
sudo supervisorctl restart crm-worker:*
```

## Quick Troubleshooting

### 502 Bad Gateway
```bash
# Check PHP-FPM
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm

# Check logs
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.3-fpm.log
```

### 500 Internal Server Error
```bash
# Check Laravel logs
tail -f /var/www/crm/backend/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/crm/backend/storage
sudo chmod -R 775 /var/www/crm/backend/storage

# Clear cache
cd /var/www/crm/backend
php artisan cache:clear
php artisan config:clear
```

### Database Connection Error
```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Test connection
psql -U crm -h localhost -d crm

# Check .env file
cat /var/www/crm/backend/.env | grep DB_

# Test from PHP
cd /var/www/crm/backend
php artisan tinker
>>> DB::connection()->getPdo();
```

### Page Not Found (404)
```bash
# Check Nginx configuration
sudo nginx -t
cat /etc/nginx/sites-available/crm

# Check if dist folder exists
ls -la /var/www/crm/frontend/dist

# Rebuild frontend
cd /var/www/crm/frontend
npm run build
```

## Performance Optimization

### Enable OPcache
```bash
# Edit PHP configuration
sudo nano /etc/php/8.3/fpm/php.ini

# Enable OPcache (add/uncomment):
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

### Enable Redis Cache (Optional)
```bash
# Install Redis
sudo apt install redis-server php8.3-redis

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Update Laravel .env
cd /var/www/crm/backend
nano .env
# Change:
# CACHE_DRIVER=redis
# SESSION_DRIVER=redis
# QUEUE_CONNECTION=redis

# Clear cache and restart
php artisan config:cache
sudo systemctl restart php8.3-fpm
```

## Backup & Restore

### Full Backup Script
```bash
#!/bin/bash
BACKUP_DIR="/backups/crm"
DATE=$(date +%Y%m%d-%H%M%S)

mkdir -p $BACKUP_DIR

# Database backup
pg_dump -U crm crm > $BACKUP_DIR/db-$DATE.sql

# Code backup
tar -czf $BACKUP_DIR/code-$DATE.tar.gz /var/www/crm

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

### Schedule Automated Backups
```bash
# Edit crontab
sudo crontab -e

# Add daily backup at 2 AM
0 2 * * * /path/to/backup-script.sh
```

## Security Commands

### Check for Updates
```bash
# Update package list
sudo apt update

# List upgradable packages
apt list --upgradable

# Upgrade all packages
sudo apt upgrade -y

# Reboot if needed
sudo reboot
```

### Monitor Failed Login Attempts
```bash
# View auth log
sudo tail -f /var/log/auth.log

# Count failed SSH attempts
sudo grep "Failed password" /var/log/auth.log | wc -l
```

---

## Quick Links

- **Deployment Guide**: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- **API Documentation**: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Quick Start**: [QUICK_START.md](QUICK_START.md)

---

**Tip**: Bookmark this file for quick reference! 📚
