# 🚀 FlowCRM - Quick Deployment (Ubuntu 24.04)

Panduan ringkas untuk deployment cepat. Untuk detail lengkap, lihat [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

---

## 📝 Persiapan

**Yang Anda Butuhkan:**
- Server Ubuntu 24.04 dengan akses SSH root/sudo
- Domain yang sudah diarahkan ke IP server
- Email untuk SSL certificate
- Password database yang kuat

---

## ⚡ Option 1: Automated Deployment (Tercepat!)

```bash
# 1. Upload kode ke server
scp -r /local/crm user@server-ip:/var/www/

# 2. SSH ke server
ssh user@server-ip

# 3. Jalankan script deployment
cd /var/www/crm
sudo bash deploy.sh

# 4. Setup aplikasi (setelah upload kode)
sudo bash setup-app.sh

# 5. Enable SSL
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

**Done! ✅** Aplikasi Anda live di `https://yourdomain.com`

---

## 📖 Option 2: Manual Deployment

### Step 1: Install Dependencies (±5 menit)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install PHP 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-pgsql \
    php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml \
    php8.3-bcmath php8.3-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs

# Install Nginx
sudo apt install -y nginx supervisor certbot python3-certbot-nginx
```

### Step 2: Setup Database (±2 menit)

```bash
sudo -u postgres psql << EOF
CREATE DATABASE crm;
CREATE USER crm WITH PASSWORD 'your_password_here';
GRANT ALL PRIVILEGES ON DATABASE crm TO crm;
ALTER DATABASE crm OWNER TO crm;
EOF
```

### Step 3: Deploy Backend (±5 menit)

```bash
cd /var/www/crm/backend

# Install dependencies
composer install --optimize-autoloader --no-dev

# Setup environment
cp .env.example .env
nano .env  # Edit DB credentials, APP_URL, dll

# Setup Laravel
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache

# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Step 4: Deploy Frontend (±3 menit)

```bash
cd /var/www/crm/frontend

# Build production
npm install
npm run build

# Fix permissions
sudo chown -R www-data:www-data dist
```

### Step 5: Configure Nginx (±3 menit)

```bash
sudo nano /etc/nginx/sites-available/crm
```

**Paste konfigurasi ini:**

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/crm/frontend/dist;
    index index.html;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
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
    
    location /storage {
        alias /var/www/crm/backend/storage/app/public;
    }
}
```

**Enable site:**

```bash
sudo ln -s /etc/nginx/sites-available/crm /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

### Step 6: Setup SSL & Firewall (±2 menit)

```bash
# Firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# SSL Certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Step 7: Setup Queue Worker (±2 menit)

```bash
sudo nano /etc/supervisor/conf.d/crm-worker.conf
```

```ini
[program:crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/crm/backend/artisan queue:work
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/crm/backend/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start crm-worker:*
```

---

## ✅ Verification

Test deployment Anda:

```bash
# Test backend API
curl https://yourdomain.com/api/

# Check services
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status postgresql
sudo supervisorctl status

# Check logs
tail -f /var/www/crm/backend/storage/logs/laravel.log
```

**Buka browser:** `https://yourdomain.com` 🎉

---

## 🔄 Update Aplikasi

```bash
cd /var/www/crm

# Backup database
pg_dump -U crm crm > backup-$(date +%Y%m%d).sql

# Update code
git pull origin main

# Backend
cd backend
composer install --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache

# Frontend
cd ../frontend
npm install
npm run build

# Restart
sudo systemctl restart nginx php8.3-fpm
sudo supervisorctl restart crm-worker:*
```

**Atau gunakan script:** `bash update-app.sh`

---

## 🆘 Troubleshooting Cepat

| Masalah | Solusi |
|---------|--------|
| 502 Bad Gateway | `sudo systemctl restart php8.3-fpm nginx` |
| 500 Error | Check: `tail -f /var/www/crm/backend/storage/logs/laravel.log` |
| Permission Error | `sudo chown -R www-data:www-data /var/www/crm/backend/storage` |
| DB Connection Error | Check `.env` credentials, test: `psql -U crm -h localhost crm` |
| CORS Error | Pastikan `SANCTUM_STATEFUL_DOMAINS` di `.env` benar |

---

## 📚 Resources

- **[Full Deployment Guide](DEPLOYMENT_GUIDE.md)** - Panduan lengkap dengan penjelasan detail
- **[Deployment Checklist](DEPLOYMENT_CHECKLIST.md)** - Checklist step-by-step
- **[Commands Reference](COMMANDS.md)** - Semua commands yang sering digunakan
- **[Scripts](/)** - `deploy.sh`, `setup-app.sh`, `update-app.sh`

---

## 🔐 Security Checklist

Setelah deployment, pastikan:

- ✅ HTTPS enabled dan valid
- ✅ Firewall (UFW) aktif
- ✅ `APP_DEBUG=false` di production
- ✅ Strong database password
- ✅ File permissions benar (storage: 775)
- ✅ Regular backups dijadwalkan
- ✅ Security updates otomatis

---

## ⏱️ Estimated Time

- **Automated Deployment:** 15-20 menit
- **Manual Deployment:** 25-30 menit (sudah termasuk SSL)

---

**Happy Deploying! 🚀**

Need help? Check logs, baca [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md), atau review checklist di [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)
