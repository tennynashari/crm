# 📋 FlowCRM Deployment Checklist

Gunakan checklist ini untuk memastikan deployment berjalan lancar.

---

## Persiapan (Sebelum Deployment)

### 1. Persiapan Domain & Server
- [ ] Domain sudah terdaftar dan DNS sudah diarahkan ke IP server
- [ ] Server Ubuntu 24.04 sudah siap dengan akses SSH
- [ ] Akses root atau sudo tersedia
- [ ] Minimal spesifikasi: 2GB RAM, 2 CPU cores, 20GB storage

### 2. Informasi yang Diperlukan
Siapkan informasi berikut:
- [ ] Nama domain (contoh: example.com)
- [ ] Email untuk SSL certificate
- [ ] Password database PostgreSQL (buat yang kuat!)
- [ ] SMTP credentials untuk email (Gmail/SendGrid/dll)

---

## Instalasi Server (Manual atau Script)

### Opsi A: Menggunakan Script Otomatis ⚡

```bash
# 1. Upload folder crm ke server
scp -r /path/to/crm user@your-server-ip:/tmp/

# 2. SSH ke server
ssh user@your-server-ip

# 3. Jalankan deployment script
cd /tmp/crm
sudo bash deploy.sh
```

- [ ] Script deploy.sh berhasil dijalankan
- [ ] PostgreSQL terinstall dan database terbuat
- [ ] PHP 8.3 dan extensions terinstall
- [ ] Node.js 20.x terinstall
- [ ] Nginx terinstall dan terkonfigurasi
- [ ] Firewall (UFW) aktif

### Opsi B: Instalasi Manual 📖

Ikuti langkah-langkah di [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

- [ ] Langkah 1: Update system ✓
- [ ] Langkah 2: Install PostgreSQL ✓
- [ ] Langkah 3: Install PHP 8.3 ✓
- [ ] Langkah 4: Install Node.js ✓
- [ ] Langkah 5: Install Composer ✓
- [ ] Langkah 6: Install Nginx ✓
- [ ] Langkah 7: Konfigurasi Firewall ✓

---

## Setup Aplikasi

### 1. Upload Kode Aplikasi
```bash
# Upload via Git
cd /var/www/crm
git clone <repository-url> .

# Atau upload manual
scp -r /local/crm/* user@server:/var/www/crm/
```

- [ ] Kode aplikasi sudah di `/var/www/crm`
- [ ] Struktur folder lengkap (backend/ dan frontend/)

### 2. Setup Backend (Laravel)

```bash
cd /var/www/crm/backend

# Install dependencies
composer install --optimize-autoloader --no-dev

# Setup environment
cp .env.example .env
nano .env  # Edit sesuai kebutuhan

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Setup storage
php artisan storage:link

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**Backend Checklist:**
- [ ] Composer dependencies terinstall
- [ ] File .env sudah dikonfigurasi dengan benar
- [ ] Database credentials sudah benar
- [ ] APP_KEY sudah digenerate
- [ ] APP_DEBUG=false untuk production
- [ ] Migrations berhasil dijalankan
- [ ] Storage link dibuat
- [ ] Cache sudah dibuild
- [ ] Permissions sudah benar

### 3. Setup Frontend (Vue.js)

```bash
cd /var/www/crm/frontend

# Install dependencies
npm install

# Buat/edit .env jika perlu
echo "VITE_API_URL=https://your-domain.com/api" > .env

# Build production
npm run build

# Fix permissions
sudo chown -R www-data:www-data dist
```

**Frontend Checklist:**
- [ ] NPM dependencies terinstall
- [ ] Environment variables sudah benar (VITE_API_URL)
- [ ] Build production berhasil (folder dist/ terbuat)
- [ ] Permissions sudah benar

---

## Konfigurasi Web Server

### 1. Nginx Configuration

- [ ] File konfigurasi dibuat di `/etc/nginx/sites-available/crm`
- [ ] Domain name sudah disesuaikan dalam config
- [ ] Symbolic link dibuat ke sites-enabled
- [ ] Nginx config test berhasil: `sudo nginx -t`
- [ ] Nginx direload: `sudo systemctl reload nginx`

### 2. Test Aplikasi (HTTP)

```bash
# Test dari server
curl http://localhost

# Test dari browser
# Buka: http://your-domain.com
```

- [ ] Aplikasi bisa diakses via HTTP
- [ ] API endpoint berfungsi: http://your-domain.com/api/
- [ ] Frontend tampil dengan benar

---

## Setup SSL/HTTPS

### 1. Install SSL Certificate

```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

**SSL Checklist:**
- [ ] Certbot berhasil mendapatkan certificate
- [ ] Nginx config otomatis diupdate untuk HTTPS
- [ ] Auto-renewal sudah dikonfigurasi
- [ ] Test renewal: `sudo certbot renew --dry-run`

### 2. Test HTTPS

```bash
# Test dari server
curl https://your-domain.com

# Buka di browser
# https://your-domain.com
```

- [ ] Aplikasi bisa diakses via HTTPS
- [ ] HTTP auto-redirect ke HTTPS
- [ ] SSL certificate valid (cek dengan browser)
- [ ] Tidak ada mixed content warning

---

## Setup Background Services

### 1. Queue Worker (Supervisor)

```bash
# Buat config
sudo nano /etc/supervisor/conf.d/crm-worker.conf

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start crm-worker:*
```

- [ ] Supervisor config dibuat
- [ ] Worker processes berjalan
- [ ] Check status: `sudo supervisorctl status`

### 2. Scheduler (Cron)

```bash
# Edit crontab
sudo crontab -e

# Tambahkan:
# * * * * * cd /var/www/crm/backend && php artisan schedule:run >> /dev/null 2>&1
```

- [ ] Cron job ditambahkan untuk Laravel scheduler

---

## Final Testing

### 1. Functional Testing
- [ ] Bisa mengakses halaman home
- [ ] Bisa login/register
- [ ] API endpoints berfungsi
- [ ] Database interaction bekerja
- [ ] File upload/download berfungsi
- [ ] Email notifikasi terkirim

### 2. Performance Testing
- [ ] Page load time < 3 detik
- [ ] API response time < 1 detik
- [ ] No 502/500 errors

### 3. Security Testing
- [ ] HTTPS aktif dan valid
- [ ] HTTP redirect ke HTTPS
- [ ] Firewall aktif (UFW)
- [ ] Port 80, 443 open; port lain closed
- [ ] APP_DEBUG=false
- [ ] Database password kuat
- [ ] File permissions benar

---

## Post-Deployment Setup

### 1. Monitoring & Logs

```bash
# Setup log rotation
sudo nano /etc/logrotate.d/crm

# Add:
/var/www/crm/backend/storage/logs/*.log {
    daily
    rotate 14
    compress
    missingok
    notifempty
}
```

- [ ] Log rotation dikonfigurasi
- [ ] Cek logs: `tail -f /var/www/crm/backend/storage/logs/laravel.log`

### 2. Backup Automation

```bash
# Buat backup script
sudo nano /usr/local/bin/backup-crm.sh

# Jadwalkan di cron
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-crm.sh
```

- [ ] Backup script dibuat
- [ ] Automated daily backup terjadwal
- [ ] Test manual backup: `pg_dump -U crm crm > test-backup.sql`
- [ ] Backup storage location ada dan cukup space

### 3. Monitoring (Optional)

```bash
# Install monitoring tools
sudo apt install htop nethogs iotop

# atau install Netdata
bash <(curl -Ss https://my-netdata.io/kickstart.sh)
```

- [ ] Monitoring tools terinstall
- [ ] Resource usage dipantau (CPU, RAM, Disk)

---

## Documentation & Handover

### 1. Document Credentials
Simpan di tempat aman (password manager):
- [ ] Server SSH credentials
- [ ] Database credentials
- [ ] Email SMTP credentials
- [ ] SSL certificate info
- [ ] Domain registrar access

### 2. Share Important Info
- [ ] Server IP address
- [ ] Domain name
- [ ] Application URL
- [ ] Admin panel access (jika ada)
- [ ] Location of backup files

### 3. Share Documentation
- [ ] [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Panduan lengkap
- [ ] [COMMANDS.md](COMMANDS.md) - Command reference
- [ ] [API_DOCUMENTATION.md](API_DOCUMENTATION.md) - API docs
- [ ] [QUICK_START.md](QUICK_START.md) - Getting started

---

## Troubleshooting Quick Reference

### Jika Ada Masalah:

**502 Bad Gateway:**
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

**500 Internal Server Error:**
```bash
tail -f /var/www/crm/backend/storage/logs/laravel.log
sudo chown -R www-data:www-data /var/www/crm/backend/storage
php artisan cache:clear
```

**Database Connection Error:**
```bash
psql -U crm -h localhost -d crm  # Test connection
cat /var/www/crm/backend/.env | grep DB_  # Check credentials
```

**Queue Not Processing:**
```bash
sudo supervisorctl status
sudo supervisorctl restart crm-worker:*
```

---

## ✅ Deployment Complete!

Selamat! Aplikasi FlowCRM Anda sudah live di production.

### Quick Links untuk Maintenance:
- Update aplikasi: Gunakan `update-app.sh`
- Check logs: `tail -f /var/www/crm/backend/storage/logs/laravel.log`
- Restart services: `sudo systemctl restart nginx php8.3-fpm`
- Database backup: `pg_dump -U crm crm > backup.sql`

### Contacts:
- Server issues: Check logs pertama
- Application issues: Check Laravel logs
- Emergency: Restore dari backup

**Happy Coding! 🚀**
