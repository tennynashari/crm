#!/bin/bash

# FlowCRM Deployment Script for Ubuntu 24.04
# Usage: sudo bash deploy.sh

set -e

echo "============================================"
echo "  FlowCRM Deployment Script"
echo "  Ubuntu 24.04"
echo "============================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root (use sudo)${NC}"
    exit 1
fi

# Get user input
echo -e "${YELLOW}Enter your domain name (e.g., example.com):${NC}"
read -r DOMAIN_NAME

echo -e "${YELLOW}Enter PostgreSQL password for 'crm' user:${NC}"
read -rs DB_PASSWORD

echo -e "${YELLOW}Enter email for SSL certificate:${NC}"
read -r EMAIL

echo ""
echo -e "${GREEN}Starting deployment...${NC}"
echo ""

# 1. Update system
echo -e "${GREEN}[1/10] Updating system...${NC}"
apt update && apt upgrade -y

# 2. Install basic dependencies
echo -e "${GREEN}[2/10] Installing basic dependencies...${NC}"
apt install -y software-properties-common curl wget git unzip supervisor ufw

# 3. Install PostgreSQL
echo -e "${GREEN}[3/10] Installing PostgreSQL...${NC}"
apt install -y postgresql postgresql-contrib

# Start and enable PostgreSQL
systemctl start postgresql
systemctl enable postgresql

# Setup database
echo -e "${GREEN}Creating database and user...${NC}"
sudo -u postgres psql << EOF
CREATE DATABASE crm;
CREATE USER crm WITH PASSWORD '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE crm TO crm;
ALTER DATABASE crm OWNER TO crm;
EOF

# 4. Install PHP 8.3
echo -e "${GREEN}[4/10] Installing PHP 8.3...${NC}"
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-pgsql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl \
    php8.3-xml php8.3-bcmath php8.3-intl php8.3-redis

# 5. Install Composer
echo -e "${GREEN}[5/10] Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# 6. Install Node.js 20.x
echo -e "${GREEN}[6/10] Installing Node.js...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# 7. Install Nginx
echo -e "${GREEN}[7/10] Installing Nginx...${NC}"
apt install -y nginx

# 8. Setup application directory
echo -e "${GREEN}[8/10] Setting up application...${NC}"
APP_DIR="/var/www/crm"

if [ ! -d "$APP_DIR" ]; then
    mkdir -p "$APP_DIR"
fi

# Set permissions
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"

# 9. Configure Nginx
echo -e "${GREEN}[9/10] Configuring Nginx...${NC}"
cat > /etc/nginx/sites-available/crm << 'NGINXCONF'
server {
    listen 80;
    listen [::]:80;
    server_name DOMAIN_NAME www.DOMAIN_NAME;
    
    root /var/www/crm/frontend/dist;
    index index.html;
    
    client_max_body_size 20M;
    
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
            fastcgi_buffer_size 16k;
            fastcgi_buffers 4 16k;
        }
    }
    
    location @api {
        rewrite ^/api/(.*)$ /api/index.php?/$1 last;
    }
    
    # Backend storage
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
NGINXCONF

# Replace domain name in Nginx config
sed -i "s/DOMAIN_NAME/$DOMAIN_NAME/g" /etc/nginx/sites-available/crm

# Enable site
ln -sf /etc/nginx/sites-available/crm /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test Nginx config
nginx -t

# Reload Nginx
systemctl reload nginx
systemctl enable nginx

# 10. Setup Firewall
echo -e "${GREEN}[10/10] Configuring firewall...${NC}"
ufw --force enable
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp

# Install Certbot for SSL
echo -e "${GREEN}Installing Certbot for SSL...${NC}"
apt install -y certbot python3-certbot-nginx

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Base Installation Complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Upload your application code to: $APP_DIR"
echo "2. Run the setup script: sudo bash $APP_DIR/setup-app.sh"
echo "3. Get SSL certificate: sudo certbot --nginx -d $DOMAIN_NAME -d www.$DOMAIN_NAME --email $EMAIL"
echo ""
echo -e "${YELLOW}Database credentials:${NC}"
echo "Database: crm"
echo "Username: crm"
echo "Password: [the password you entered]"
echo "Host: localhost"
echo "Port: 5432"
echo ""
