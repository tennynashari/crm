# Multi-Tenant CRM - Server Deployment Guide

Panduan lengkap deployment multi-tenant CRM system ke production server.

---

## 📋 Prerequisites

- Ubuntu Server 24.04 / 22.04 / 20.04
- Root atau sudo access
- Domain (optional, bisa pakai IP)

---

## STEP 1: Install PostgreSQL

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PostgreSQL
sudo apt install postgresql postgresql-contrib -y

# Check status
sudo systemctl status postgresql

# Start PostgreSQL if not running
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

---

## STEP 2: Create PostgreSQL User

```bash
# Login as postgres user
sudo -u postgres psql

# Di dalam psql console:
CREATE USER crm WITH PASSWORD 'crm123';
ALTER USER crm CREATEDB;  -- Allow user to create databases

# Exit psql
\q
```

---

## STEP 3: Create Databases

```bash
# Login as postgres user
sudo -u postgres psql

# Di dalam psql console:
-- Create master database
CREATE DATABASE crm_master OWNER crm;

-- Create tenant database untuk Main Company
CREATE DATABASE crm OWNER crm;

-- Create tenant database untuk EcoGreen
CREATE DATABASE crm_ecogreen OWNER crm;

-- Grant all privileges
GRANT ALL PRIVILEGES ON DATABASE crm_master TO crm;
GRANT ALL PRIVILEGES ON DATABASE crm TO crm;
GRANT ALL PRIVILEGES ON DATABASE crm_ecogreen TO crm;

-- List databases untuk verify
\l

-- Exit
\q
```

### Verify Databases Created:
```bash
sudo -u postgres psql -l
```

Expected output:
```
        Name         | Owner | Encoding 
---------------------|-------|----------
 crm                 | crm   | UTF8
 crm_ecogreen        | crm   | UTF8
 crm_master          | crm   | UTF8
```

---

## STEP 4: Configure PostgreSQL for Remote Access (Optional)

Jika database server terpisah dari app server:

```bash
# Edit postgresql.conf
sudo nano /etc/postgresql/14/main/postgresql.conf

# Find and change:
listen_addresses = '*'

# Edit pg_hba.conf
sudo nano /etc/postgresql/14/main/pg_hba.conf

# Add at the end:
host    all             all             0.0.0.0/0               md5

# Restart PostgreSQL
sudo systemctl restart postgresql
```

---

## STEP 5: Upload Application Code

```bash
# Di local machine, compress code
cd e:\Code\crm
tar -czf crm-app.tar.gz backend/ frontend/

# Upload ke server (via SCP/SFTP)
scp crm-app.tar.gz user@your-server-ip:/home/user/

# Di server, extract
cd /var/www/
sudo mkdir crm
sudo tar -xzf /home/user/crm-app.tar.gz -C crm/
sudo chown -R www-data:www-data crm/
```

---

## STEP 6: Install PHP & Dependencies

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 and extensions
sudo apt install -y php8.3 php8.3-fpm php8.3-pgsql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verify installations
php -v
composer -V
```

---

## STEP 7: Setup Backend

```bash
# Navigate to backend
cd /var/www/crm/backend

# Install dependencies
composer install --optimize-autoloader --no-dev

# Copy and configure .env
cp .env.example .env
nano .env
```

### Configure `.env`:
```env
APP_NAME="FlowCRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=master
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=crm
DB_MASTER_DATABASE=crm_master
DB_USERNAME=crm
DB_PASSWORD=crm123

# Frontend URL
FRONTEND_URL=https://your-domain.com
SESSION_DOMAIN=your-domain.com
SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

```bash
# Generate app key
php artisan key:generate

# Set permissions
sudo chown -R www-data:www-data /var/www/crm/backend/storage
sudo chown -R www-data:www-data /var/www/crm/backend/bootstrap/cache
sudo chmod -R 775 /var/www/crm/backend/storage
sudo chmod -R 775 /var/www/crm/backend/bootstrap/cache
```

---

## STEP 8: Run Migrations

```bash
cd /var/www/crm/backend

# Clear config cache
php artisan config:clear

# Migrate master database
php artisan migrate --database=master --path=database/migrations/master --force

# Check master database
php artisan tinker --execute="echo 'Master tables: '; DB::connection('master')->select('SELECT tablename FROM pg_tables WHERE schemaname = \'public\'');"
```

---

## STEP 9: Setup Tenant Databases

### Option A: Migrate Existing Data (Database CRM sudah ada data)

```bash
# Upload helper scripts
cd /var/www/crm/backend

# Run tenant migrations on existing crm database
php artisan migrate --database=pgsql --path=database/migrations/tenant --force

# Run existing migrations on crm database
php artisan migrate --database=pgsql --force

# Register Main Company and migrate users
php migrate_existing_company.php
```

### Option B: Create New Tenant (EcoGreen)

```bash
# Using artisan command
php artisan tenant:create "EcoGreen" "andhia@ecogreen.id" "andhia123@@"

# Or using helper script
php setup_ecogreen.php

# Clean EcoGreen database (keep only admin)
php clean_ecogreen.php
```

### Verify Setup:
```bash
php verify_setup.php
```

Expected output:
```
COMPANY: EcoGreen
Database: crm_ecogreen
User Profiles: 1
Customers: 0

COMPANY: Main Company
Database: crm
User Profiles: 5
Customers: 122
```

---

## STEP 10: Install & Configure Nginx

```bash
# Install Nginx
sudo apt install nginx -y

# Create site configuration
sudo nano /etc/nginx/sites-available/crm
```

### Nginx Configuration:
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/crm/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/crm /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

---

## STEP 11: Setup SSL with Let's Encrypt (Optional but Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal is configured automatically
# Test renewal
sudo certbot renew --dry-run
```

---

## STEP 12: Setup Frontend (Vue.js)

```bash
# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Navigate to frontend
cd /var/www/crm/frontend

# Install dependencies
npm install

# Build for production
npm run build

# Serve with Nginx
sudo nano /etc/nginx/sites-available/crm-frontend
```

### Frontend Nginx Config:
```nginx
server {
    listen 80;
    server_name app.your-domain.com;
    root /var/www/crm/frontend/dist;

    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

```bash
# Enable and restart
sudo ln -s /etc/nginx/sites-available/crm-frontend /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## STEP 13: Setup Process Manager (Supervisor for Queue Workers)

```bash
# Install Supervisor
sudo apt install supervisor -y

# Create worker configuration
sudo nano /etc/supervisor/conf.d/crm-worker.conf
```

```ini
[program:crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/crm/backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/crm/backend/storage/logs/worker.log
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start crm-worker:*

# Check status
sudo supervisorctl status
```

---

## STEP 14: Configure Firewall

```bash
# Install UFW if not installed
sudo apt install ufw -y

# Allow SSH (important!)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow PostgreSQL (if remote access needed)
sudo ufw allow 5432/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

---

## STEP 15: Setup Backup Script

```bash
# Create backup directory
sudo mkdir -p /var/backups/crm

# Create backup script
sudo nano /usr/local/bin/backup-crm.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/crm"

# Backup databases
pg_dump -U crm -h localhost crm_master > $BACKUP_DIR/crm_master_$DATE.sql
pg_dump -U crm -h localhost crm > $BACKUP_DIR/crm_$DATE.sql
pg_dump -U crm -h localhost crm_ecogreen > $BACKUP_DIR/crm_ecogreen_$DATE.sql

# Backup application files
tar -czf $BACKUP_DIR/app_$DATE.tar.gz /var/www/crm/backend/storage

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-crm.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
# Add line:
0 2 * * * /usr/local/bin/backup-crm.sh
```

---

## STEP 16: Test Multi-Tenant Login

```bash
# Test API endpoint
curl -X POST https://your-domain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@flowcrm.test",
    "password": "your_password"
  }'

# Expected response:
{
  "message": "Login successful",
  "user": {
    "email": "admin@flowcrm.test",
    "company": {
      "name": "Main Company",
      "database": "crm"
    }
  }
}

# Test EcoGreen login
curl -X POST https://your-domain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "andhia@ecogreen.id",
    "password": "andhia123@@"
  }'
```

---

## STEP 17: Add New Tenant (Future)

```bash
cd /var/www/crm/backend

# Create new tenant
php artisan tenant:create "New Company" "admin@newcompany.com" "password123"

# This will:
# 1. Create database: crm_new_company
# 2. Run migrations
# 3. Create admin user
# 4. Seed initial data (optional)
```

---

## 📊 Monitoring & Maintenance

### Check Application Logs:
```bash
tail -f /var/www/crm/backend/storage/logs/laravel.log
```

### Check Nginx Logs:
```bash
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log
```

### Check Database Connections:
```bash
sudo -u postgres psql -c "SELECT datname, numbackends FROM pg_stat_database;"
```

### Restart Services:
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo supervisorctl restart crm-worker:*
```

---

## 🔒 Security Checklist

- [ ] Strong database passwords
- [ ] SSL/TLS enabled (HTTPS)
- [ ] Firewall configured (UFW)
- [ ] APP_DEBUG=false in production
- [ ] Regular backups configured
- [ ] File permissions correct (775 for storage)
- [ ] PostgreSQL not exposed publicly (unless needed)
- [ ] Fail2ban installed (optional)
- [ ] Regular system updates

---

## 🚀 Quick Commands Reference

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check multi-tenant setup
php verify_setup.php

# Add new company
php artisan tenant:create "Company Name" "admin@email.com" "password"

# Database backup
/usr/local/bin/backup-crm.sh

# Restart all services
sudo systemctl restart php8.3-fpm nginx
sudo supervisorctl restart crm-worker:*
```

---

## ✅ Deployment Completed!

Your multi-tenant CRM system is now live at:
- **Backend API:** https://your-domain.com/api
- **Frontend:** https://app.your-domain.com (if separate)

**Login Credentials:**
- Main Company: admin@flowcrm.test
- EcoGreen: andhia@ecogreen.id (password: andhia123@@)

---

## 📞 Troubleshooting

### Database Connection Error:
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Test connection
sudo -u postgres psql -U crm -d crm_master -c "SELECT 1;"
```

### Permission Denied:
```bash
sudo chown -R www-data:www-data /var/www/crm/backend/storage
sudo chmod -R 775 /var/www/crm/backend/storage
```

### Session Issues:
```bash
# Clear Laravel cache
php artisan cache:clear
php artisan config:clear

# Check session driver in .env
SESSION_DRIVER=cookie  # or database
```

