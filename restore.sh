#!/bin/bash

# FlowCRM Restore Script
# Restores database and application files from backup
# Usage: bash restore.sh [backup-date]
# Example: bash restore.sh 20260212-140530

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
BACKUP_DIR="/backups/crm"
APP_DIR="/var/www/crm"
DB_NAME="crm"
DB_USER="crm"

echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  FlowCRM Restore Script${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""

# Check if backup directory exists
if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}Error: Backup directory $BACKUP_DIR not found${NC}"
    exit 1
fi

# List available backups
echo -e "${YELLOW}Available backups:${NC}"
echo ""
ls -lh "$BACKUP_DIR"/db-*.sql* 2>/dev/null | awk '{print $9}' | xargs -n1 basename | sed 's/db-//;s/.sql.*$//' | nl
echo ""

# Get backup date from argument or prompt user
if [ -n "$1" ]; then
    BACKUP_DATE="$1"
else
    echo -e "${YELLOW}Enter backup date to restore (format: YYYYMMDD-HHMMSS):${NC}"
    read -r BACKUP_DATE
fi

# Verify backup files exist
DB_BACKUP="$BACKUP_DIR/db-$BACKUP_DATE.sql"
DB_BACKUP_GZ="$BACKUP_DIR/db-$BACKUP_DATE.sql.gz"
CODE_BACKUP="$BACKUP_DIR/code-$BACKUP_DATE.tar.gz"
STORAGE_BACKUP="$BACKUP_DIR/storage-$BACKUP_DATE.tar.gz"

if [ ! -f "$DB_BACKUP" ] && [ ! -f "$DB_BACKUP_GZ" ]; then
    echo -e "${RED}Error: Database backup not found for date: $BACKUP_DATE${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}⚠️  WARNING: This will overwrite current data!${NC}"
echo -e "${YELLOW}Do you want to continue? (yes/no)${NC}"
read -r CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${RED}Restore cancelled.${NC}"
    exit 0
fi

# Create a backup of current state before restoring
echo ""
echo -e "${YELLOW}[1/5] Creating safety backup of current state...${NC}"
SAFETY_BACKUP="$BACKUP_DIR/safety-backup-$(date +%Y%m%d-%H%M%S).sql"
PGPASSWORD="your_db_password" pg_dump -U "$DB_USER" -h localhost "$DB_NAME" > "$SAFETY_BACKUP"
echo -e "${GREEN}✓ Safety backup created: $SAFETY_BACKUP${NC}"

# Put application in maintenance mode
echo -e "${YELLOW}[2/5] Enabling maintenance mode...${NC}"
cd "$APP_DIR/backend"
php artisan down --retry=60
echo -e "${GREEN}✓ Maintenance mode enabled${NC}"

# Restore Database
echo -e "${YELLOW}[3/5] Restoring database...${NC}"

# Drop and recreate database
sudo -u postgres psql << EOF
DROP DATABASE IF EXISTS ${DB_NAME}_restore;
CREATE DATABASE ${DB_NAME}_restore OWNER ${DB_USER};
EOF

# Restore from backup
if [ -f "$DB_BACKUP" ]; then
    PGPASSWORD="your_db_password" psql -U "$DB_USER" -h localhost "${DB_NAME}_restore" < "$DB_BACKUP"
elif [ -f "$DB_BACKUP_GZ" ]; then
    gunzip -c "$DB_BACKUP_GZ" | PGPASSWORD="your_db_password" psql -U "$DB_USER" -h localhost "${DB_NAME}_restore"
fi

# Swap databases
sudo -u postgres psql << EOF
ALTER DATABASE ${DB_NAME} RENAME TO ${DB_NAME}_old;
ALTER DATABASE ${DB_NAME}_restore RENAME TO ${DB_NAME};
EOF

echo -e "${GREEN}✓ Database restored${NC}"

# Restore Code (optional)
if [ -f "$CODE_BACKUP" ]; then
    echo -e "${YELLOW}[4/5] Do you want to restore application code? (yes/no)${NC}"
    read -r RESTORE_CODE
    
    if [ "$RESTORE_CODE" = "yes" ]; then
        echo -e "${YELLOW}Restoring code...${NC}"
        
        # Backup current code
        mv "$APP_DIR" "${APP_DIR}_old_$(date +%Y%m%d-%H%M%S)"
        
        # Extract backup
        mkdir -p "$APP_DIR"
        tar -xzf "$CODE_BACKUP" -C "$(dirname $APP_DIR)"
        
        # Reinstall dependencies
        cd "$APP_DIR/backend"
        composer install --optimize-autoloader --no-dev
        
        cd "$APP_DIR/frontend"
        npm install
        npm run build
        
        # Fix permissions
        sudo chown -R www-data:www-data "$APP_DIR"
        sudo chmod -R 775 "$APP_DIR/backend/storage"
        sudo chmod -R 775 "$APP_DIR/backend/bootstrap/cache"
        
        echo -e "${GREEN}✓ Code restored${NC}"
    fi
else
    echo -e "${YELLOW}[4/5] Code backup not found, skipping...${NC}"
fi

# Restore Storage (optional)
if [ -f "$STORAGE_BACKUP" ]; then
    echo -e "${YELLOW}[5/5] Do you want to restore storage files? (yes/no)${NC}"
    read -r RESTORE_STORAGE
    
    if [ "$RESTORE_STORAGE" = "yes" ]; then
        echo -e "${YELLOW}Restoring storage...${NC}"
        tar -xzf "$STORAGE_BACKUP" -C "$APP_DIR/backend/storage/"
        sudo chown -R www-data:www-data "$APP_DIR/backend/storage/app"
        echo -e "${GREEN}✓ Storage restored${NC}"
    fi
else
    echo -e "${YELLOW}[5/5] Storage backup not found, skipping...${NC}"
fi

# Restart services
echo ""
echo -e "${YELLOW}Restarting services...${NC}"
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo supervisorctl restart crm-worker:*

# Disable maintenance mode
cd "$APP_DIR/backend"
php artisan up

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Restore Complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${YELLOW}Restored from backup: $BACKUP_DATE${NC}"
echo -e "${YELLOW}Safety backup saved to: $SAFETY_BACKUP${NC}"
echo ""
echo -e "${YELLOW}Old database renamed to: ${DB_NAME}_old${NC}"
echo -e "${YELLOW}You can drop it with:${NC}"
echo "  sudo -u postgres psql -c 'DROP DATABASE ${DB_NAME}_old;'"
echo ""
echo -e "${GREEN}✓ Application is back online!${NC}"
echo ""
