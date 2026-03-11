# Quick Fix: Migration Error

## Problem
```
Error: Permission denied for laravel.log
Error: password authentication failed for user "crm"
```

## Solution (Run these commands in order)

### 1. Fix Storage Permissions
```bash
cd /var/www/crm/backend
sudo chown -R www-data:www-data storage/ bootstrap/cache/
sudo chmod -R 775 storage/ bootstrap/cache/
```

### 2. Fix PostgreSQL Authentication
```bash
# Method A: Update pg_hba.conf
sudo nano /etc/postgresql/14/main/pg_hba.conf

# Find and change these lines:
# FROM:
local   all             all                                     peer

# TO:
local   all             all                                     md5

# Save (Ctrl+X, Y, Enter)
sudo systemctl restart postgresql
```

### 3. Verify Database Password
```bash
# Reset password
sudo -u postgres psql
ALTER USER crm WITH PASSWORD 'crm123';
\q

# Test connection
psql -U crm -d crm_master -h localhost -W
# Enter password: crm123
# Should connect successfully
# Type \q to exit
```

### 4. Update .env (if needed)
```bash
cd /var/www/crm/backend
nano .env

# Make sure:
DB_HOST=127.0.0.1   # Use 127.0.0.1 instead of localhost
DB_USERNAME=crm
DB_PASSWORD=crm123

# Clear cache
php artisan config:clear
```

### 5. Run Migration Again
```bash
cd /var/www/crm/backend
php artisan migrate --database=master --path=database/migrations/master --force
```

## Test All Databases
```bash
# Test connections
psql -U crm -d crm_master -h 127.0.0.1 -c "SELECT 1;"
psql -U crm -d crm -h 127.0.0.1 -c "SELECT 1;"
psql -U crm -d crm_ecogreen -h 127.0.0.1 -c "SELECT 1;"

# All should return: 1
```

## If Still Error: Fresh Database Setup
```bash
sudo -u postgres psql

DROP DATABASE IF EXISTS crm_master;
DROP USER IF EXISTS crm;

CREATE USER crm WITH PASSWORD 'crm123';
CREATE DATABASE crm_master OWNER crm;
GRANT ALL PRIVILEGES ON DATABASE crm_master TO crm;
\q

# Try migration again
cd /var/www/crm/backend
php artisan migrate --database=master --path=database/migrations/master --force
```
