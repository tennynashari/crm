#!/bin/bash

# FlowCRM Update Script
# Usage: bash update-app.sh

set -e

APP_DIR="/var/www/crm"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "============================================"
echo "  FlowCRM Update Script"
echo "============================================"
echo ""

# Backup database first
echo -e "${YELLOW}Creating database backup...${NC}"
BACKUP_FILE="$APP_DIR/backup-$(date +%Y%m%d-%H%M%S).sql"
sudo -u postgres pg_dump crm > "$BACKUP_FILE"
echo -e "${GREEN}Backup created: $BACKUP_FILE${NC}"
echo ""

# Put application in maintenance mode
echo -e "${YELLOW}Enabling maintenance mode...${NC}"
cd "$APP_DIR/backend"
php artisan down

# Pull latest code
echo -e "${GREEN}Pulling latest code...${NC}"
cd "$APP_DIR"
git pull origin main

# Update backend
echo -e "${GREEN}Updating backend...${NC}"
cd "$APP_DIR/backend"

# Install/update composer dependencies
composer install --optimize-autoloader --no-dev --no-interaction

# Run migrations
php artisan migrate --force

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Update frontend
echo -e "${GREEN}Updating frontend...${NC}"
cd "$APP_DIR/frontend"

# Install/update npm dependencies
npm install

# Rebuild frontend
npm run build

# Fix permissions
chown -R www-data:www-data dist

# Restart services
echo -e "${GREEN}Restarting services...${NC}"
systemctl restart php8.3-fpm
systemctl restart nginx
supervisorctl restart crm-worker:*

# Bring application back online
echo -e "${GREEN}Disabling maintenance mode...${NC}"
cd "$APP_DIR/backend"
php artisan up

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Update Complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${YELLOW}Database backup saved to:${NC}"
echo "$BACKUP_FILE"
echo ""
