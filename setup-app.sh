#!/bin/bash

# FlowCRM Application Setup Script
# Run this after uploading your code to /var/www/crm

set -e

APP_DIR="/var/www/crm"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "============================================"
echo "  FlowCRM Application Setup"
echo "============================================"
echo ""

# Check if app directory exists
if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}Error: Application directory $APP_DIR not found${NC}"
    exit 1
fi

# Get database password
echo -e "${YELLOW}Enter PostgreSQL password for 'crm' user:${NC}"
read -rs DB_PASSWORD

echo -e "${YELLOW}Enter your domain name (e.g., example.com):${NC}"
read -r DOMAIN_NAME

echo ""
echo -e "${GREEN}Setting up backend...${NC}"

# Setup Backend
cd "$APP_DIR/backend"

# Install composer dependencies
echo -e "${GREEN}Installing composer dependencies...${NC}"
composer install --optimize-autoloader --no-dev --no-interaction

# Setup .env file
if [ ! -f .env ]; then
    echo -e "${GREEN}Creating .env file...${NC}"
    cp .env.example .env
    
    # Generate app key
    php artisan key:generate --force
    
    # Update database credentials
    sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=crm/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=crm/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
    
    # Set production settings
    sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
    sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN_NAME|" .env
    
    # Set CORS and session domains
    echo "" >> .env
    echo "SANCTUM_STATEFUL_DOMAINS=$DOMAIN_NAME,www.$DOMAIN_NAME" >> .env
    echo "SESSION_DOMAIN=.$DOMAIN_NAME" >> .env
fi

# Run migrations
echo -e "${GREEN}Running database migrations...${NC}"
php artisan migrate --force

# Seed database (optional)
echo -e "${YELLOW}Do you want to seed the database with sample data? (y/n)${NC}"
read -r SEED_DB
if [ "$SEED_DB" = "y" ] || [ "$SEED_DB" = "Y" ]; then
    php artisan db:seed --force
fi

# Create storage link
php artisan storage:link

# Cache configuration
echo -e "${GREEN}Caching configuration...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo -e "${GREEN}Setting permissions...${NC}"
chown -R www-data:www-data "$APP_DIR/backend/storage"
chown -R www-data:www-data "$APP_DIR/backend/bootstrap/cache"
chmod -R 775 "$APP_DIR/backend/storage"
chmod -R 775 "$APP_DIR/backend/bootstrap/cache"

echo ""
echo -e "${GREEN}Setting up frontend...${NC}"

# Setup Frontend
cd "$APP_DIR/frontend"

# Update API URL in vite.config.js or create .env
if [ ! -f .env ]; then
    echo "VITE_API_URL=https://$DOMAIN_NAME/api" > .env
fi

# Install npm dependencies
echo -e "${GREEN}Installing npm dependencies...${NC}"
npm install

# Build for production
echo -e "${GREEN}Building frontend for production...${NC}"
npm run build

# Set permissions
chown -R www-data:www-data "$APP_DIR/frontend/dist"

# Setup Supervisor for Queue Worker
echo -e "${GREEN}Setting up queue worker...${NC}"
cat > /etc/supervisor/conf.d/crm-worker.conf << EOF
[program:crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_DIR/backend/storage/logs/worker.log
stopwaitsecs=3600
EOF

# Reload supervisor
supervisorctl reread
supervisorctl update
supervisorctl start crm-worker:*

# Restart services
echo -e "${GREEN}Restarting services...${NC}"
systemctl restart php8.3-fpm
systemctl restart nginx

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Application Setup Complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${YELLOW}Your application is now running at:${NC}"
echo "http://$DOMAIN_NAME"
echo ""
echo -e "${YELLOW}To enable HTTPS, run:${NC}"
echo "sudo certbot --nginx -d $DOMAIN_NAME -d www.$DOMAIN_NAME"
echo ""
echo -e "${YELLOW}Check application logs:${NC}"
echo "Laravel: tail -f $APP_DIR/backend/storage/logs/laravel.log"
echo "Nginx: tail -f /var/log/nginx/error.log"
echo "Worker: tail -f $APP_DIR/backend/storage/logs/worker.log"
echo ""
