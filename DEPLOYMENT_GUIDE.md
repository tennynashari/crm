# Panduan Deployment FlowCRM di Ubuntu 24.04

Panduan lengkap untuk deploy aplikasi CRM ini ke server Ubuntu 24.04 dengan PostgreSQL, Nginx, dan SSL.

## Daftar Isi
1. [Persiapan Server](#1-persiapan-server)
2. [Instalasi PostgreSQL](#2-instalasi-postgresql)
3. [Instalasi PHP dan Dependencies](#3-instalasi-php-dan-dependencies)
4. [Instalasi Node.js dan NPM](#4-instalasi-nodejs-dan-npm)
5. [Setup Aplikasi](#5-setup-aplikasi)
6. [Konfigurasi Nginx](#6-konfigurasi-nginx)
7. [Setup SSL dengan Let's Encrypt](#7-setup-ssl-dengan-lets-encrypt)
8. [Konfigurasi Firewall](#8-konfigurasi-firewall)
9. [Setup Process Manager (PM2)](#9-setup-process-manager-optional)
10. [Maintenance & Troubleshooting](#10-maintenance--troubleshooting)

---

## 1. Persiapan Server

### Update System
```bash
sudo apt update && sudo apt upgrade -y
```

### Install Dependencies Dasar
```bash
sudo apt install -y software-properties-common curl wget git unzip supervisor
```

### Buat User untuk Aplikasi (Opsional tapi Recommended)
```bash
sudo adduser crm
sudo usermod -aG sudo crm
su - crm
```

---

## 2. Instalasi PostgreSQL

### Install PostgreSQL 16
```bash
sudo apt install -y postgresql postgresql-contrib
```

### Cek Status PostgreSQL
```bash
sudo systemctl status postgresql
sudo systemctl enable postgresql
```

### Setup Database dan User
```bash
# Masuk ke PostgreSQL
sudo -u postgres psql

# Di dalam PostgreSQL prompt, jalankan:
CREATE DATABASE crm;
CREATE USER crm WITH PASSWORD 'password_yang_kuat_disini';
GRANT ALL PRIVILEGES ON DATABASE crm TO crm;
ALTER DATABASE crm OWNER TO crm;

# Keluar dari PostgreSQL
\q
```

### Konfigurasi PostgreSQL untuk Remote Access (Jika Diperlukan)
```bash
# Edit pg_hba.conf
sudo nano /etc/postgresql/16/main/pg_hba.conf

# Tambahkan baris ini (sesuaikan dengan kebutuhan):
# host    all             all             0.0.0.0/0               scram-sha-256

# Edit postgresql.conf
sudo nano /etc/postgresql/16/main/postgresql.conf

# Ubah listen_addresses:
# listen_addresses = '*'

# Restart PostgreSQL
sudo systemctl restart postgresql
```

---

## 3. Instalasi PHP dan Dependencies

### Install PHP 8.2/8.3
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-pgsql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl \
    php8.3-xml php8.3-bcmath php8.3-intl php8.3-redis
```

### Verifikasi Instalasi PHP
```bash
php -v
php -m | grep pgsql
```

### Install Composer
```bash
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

---

## 4. Instalasi Node.js dan NPM

### Install Node.js 20.x (LTS)
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Verifikasi Instalasi
```bash
node --version
npm --version
```

### Install Yarn (Opsional)
```bash
sudo npm install -g yarn
```

---

## 5. Setup Aplikasi

### Clone atau Upload Aplikasi
```bash
# Jika menggunakan Git
cd /var/www
sudo mkdir -p crm
sudo chown -R $USER:$USER /var/www/crm
git clone <repository-url> /var/www/crm

# Atau upload manual menggunakan scp/sftp
# scp -r /path/to/local/crm user@server:/var/www/
```

### Setup Backend (Laravel)

```bash
cd /var/www/crm/backend

# Install dependencies
composer install --optimize-autoloader --no-dev

# Copy environment file
cp .env.example .env

# Edit .env file
nano .env
```

### Konfigurasi .env untuk Production
```env
APP_NAME="FlowCRM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=crm
DB_USERNAME=crm
DB_PASSWORD=password_yang_kuat_disini

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Mail Configuration (sesuaikan dengan SMTP Anda)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# CORS
SANCTUM_STATEFUL_DOMAINS=your-domain.com,www.your-domain.com
SESSION_DOMAIN=.your-domain.com
```

### Generate Application Key & Setup Database
```bash
# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed database (jika ada)
php artisan db:seed --force

# Clear & cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Setup storage permissions
chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Setup Frontend (Vue.js)

```bash
cd /var/www/crm/frontend

# Install dependencies
npm install

# Edit environment file (jika ada)
nano .env
```

### Konfigurasi .env Frontend (atau edit vite.config.js)
```env
VITE_API_URL=https://your-domain.com/api
```

### Build Production
```bash
npm run build

# File hasil build ada di folder dist/
```

---

## 6. Konfigurasi Nginx

### Install Nginx
```bash
sudo apt install -y nginx
```

### Buat Konfigurasi Site
```bash
sudo nano /etc/nginx/sites-available/crm
```

### Konfigurasi Nginx untuk Laravel + Vue (SPA)
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    
    root /var/www/crm/frontend/dist;
    index index.html;
    
    # Frontend (Vue SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Backend API (Laravel)
    location /api {
        alias /var/www/crm/backend/public;
        try_files $uri $uri/ @api;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME /var/www/crm/backend/public/index.php;
            include fastcgi_params;
        }
    }
    
    location @api {
        rewrite ^/api/(.*)$ /api/index.php?/$1 last;
    }
    
    # Backend storage (untuk file uploads)
    location /storage {
        alias /var/www/crm/backend/storage/app/public;
        try_files $uri $uri/ =404;
    }
    
    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;
}
```

### Aktifkan Site
```bash
# Link konfigurasi
sudo ln -s /etc/nginx/sites-available/crm /etc/nginx/sites-enabled/

# Test konfigurasi
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
sudo systemctl enable nginx
```

---

## 7. Setup SSL dengan Let's Encrypt

### Install Certbot
```bash
sudo apt install -y certbot python3-certbot-nginx
```

### Dapatkan SSL Certificate
```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

### Verifikasi Auto-Renewal
```bash
sudo certbot renew --dry-run
```

Certbot akan otomatis menambahkan konfigurasi HTTPS ke file Nginx Anda.

---

## 8. Konfigurasi Firewall

### Setup UFW (Uncomplicated Firewall)
```bash
# Install UFW (biasanya sudah terinstall)
sudo apt install -y ufw

# Allow SSH (PENTING! Sebelum enable UFW)
sudo ufw allow 22/tcp

# Allow HTTP & HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow PostgreSQL (hanya jika perlu akses eksternal)
# sudo ufw allow 5432/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

---

## 9. Setup Process Manager (Optional)

### Install Supervisor untuk Laravel Queue Worker
```bash
sudo apt install -y supervisor
```

### Konfigurasi Queue Worker
```bash
sudo nano /etc/supervisor/conf.d/crm-worker.conf
```

### Isi Konfigurasi Worker
```ini
[program:crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/crm/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/crm/backend/storage/logs/worker.log
stopwaitsecs=3600
```

### Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start crm-worker:*
sudo supervisorctl status
```

---

## 10. Maintenance & Troubleshooting

### Logs Location
```bash
# Nginx logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Laravel logs
tail -f /var/www/crm/backend/storage/logs/laravel.log

# PHP-FPM logs
tail -f /var/log/php8.3-fpm.log

# PostgreSQL logs
sudo tail -f /var/log/postgresql/postgresql-16-main.log
```

### Common Commands

```bash
# Clear Laravel cache
cd /var/www/crm/backend
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo systemctl restart postgresql

# Check service status
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status postgresql
```

### Database Backup
```bash
# Backup database
pg_dump -U crm -h localhost crm > backup-$(date +%Y%m%d-%H%M%S).sql

# Restore database
psql -U crm -h localhost crm < backup.sql
```

### Update Aplikasi
```bash
# Backend
cd /var/www/crm/backend
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
cd /var/www/crm/frontend
git pull origin main
npm install
npm run build

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

### Performance Optimization

```bash
# Install Redis (optional, untuk cache & session)
sudo apt install -y redis-server
sudo systemctl enable redis-server

# Update .env untuk menggunakan Redis
# CACHE_DRIVER=redis
# SESSION_DRIVER=redis
# QUEUE_CONNECTION=redis

# Install PHP Redis extension
sudo apt install -y php8.3-redis
sudo systemctl restart php8.3-fpm
```

### Security Checklist
- ✅ Firewall aktif (UFW)
- ✅ SSL/HTTPS enabled
- ✅ APP_DEBUG=false di production
- ✅ Strong database passwords
- ✅ File permissions correct (775 untuk storage, 644 untuk files)
- ✅ Disable root SSH login
- ✅ Regular security updates
- ✅ Database backups terjadwal
- ✅ Rate limiting di API

---

## Troubleshooting Common Issues

### Issue: 502 Bad Gateway
```bash
# Check PHP-FPM status
sudo systemctl status php8.3-fpm

# Check socket file exists
ls -la /var/run/php/php8.3-fpm.sock

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

### Issue: Permission Denied
```bash
# Fix Laravel permissions
cd /var/www/crm/backend
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Issue: Database Connection Failed
```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Test connection
psql -U crm -h localhost -d crm

# Check pg_hba.conf for authentication settings
sudo nano /etc/postgresql/16/main/pg_hba.conf
```

### Issue: CORS Errors
Pastikan di `backend/.env`:
```env
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=.your-domain.com
```

Dan di `backend/config/cors.php` sudah sesuai.

---

## Monitoring & Alerts (Bonus)

### Install Server Monitoring (Optional)
```bash
# Install netdata untuk monitoring real-time
bash <(curl -Ss https://my-netdata.io/kickstart.sh)

# Akses via: http://your-server-ip:19999
```

---

## Testing Deployment

### 1. Test Backend API
```bash
curl https://your-domain.com/api/health
```

### 2. Test Frontend
Buka browser: `https://your-domain.com`

### 3. Test Database Connection
```bash
cd /var/www/crm/backend
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## Kontak & Support

Jika ada masalah, periksa:
1. Nginx error logs: `/var/log/nginx/error.log`
2. Laravel logs: `/var/www/crm/backend/storage/logs/laravel.log`
3. PHP-FPM logs: `/var/log/php8.3-fpm.log`

Selamat deploying! 🚀
