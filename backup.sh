#!/bin/bash

# FlowCRM Backup Script
# Backs up database and application files
# Add to crontab: 0 2 * * * /var/www/crm/backup.sh

set -e

# Configuration
APP_DIR="/var/www/crm"
BACKUP_DIR="/backups/crm"
DB_NAME="crm"
DB_USER="crm"
RETENTION_DAYS=7

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Date format
DATE=$(date +%Y%m%d-%H%M%S)
TODAY=$(date +%Y-%m-%d)

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  FlowCRM Backup - $TODAY${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""

# 1. Backup Database
echo -e "${YELLOW}[1/4] Backing up PostgreSQL database...${NC}"
PGPASSWORD="your_db_password" pg_dump -U "$DB_USER" -h localhost "$DB_NAME" > "$BACKUP_DIR/db-$DATE.sql"

if [ $? -eq 0 ]; then
    DB_SIZE=$(du -h "$BACKUP_DIR/db-$DATE.sql" | cut -f1)
    echo -e "${GREEN}✓ Database backup completed: db-$DATE.sql ($DB_SIZE)${NC}"
else
    echo -e "${RED}✗ Database backup failed!${NC}"
    exit 1
fi

# 2. Backup Application Code (excluding vendor, node_modules, storage)
echo -e "${YELLOW}[2/4] Backing up application code...${NC}"
tar -czf "$BACKUP_DIR/code-$DATE.tar.gz" \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    --exclude='backend/bootstrap/cache' \
    --exclude='frontend/dist' \
    --exclude='.git' \
    -C "$(dirname $APP_DIR)" "$(basename $APP_DIR)" 2>/dev/null

if [ $? -eq 0 ]; then
    CODE_SIZE=$(du -h "$BACKUP_DIR/code-$DATE.tar.gz" | cut -f1)
    echo -e "${GREEN}✓ Code backup completed: code-$DATE.tar.gz ($CODE_SIZE)${NC}"
else
    echo -e "${YELLOW}⚠ Code backup completed with warnings${NC}"
fi

# 3. Backup Storage (user uploads, etc)
echo -e "${YELLOW}[3/4] Backing up storage files...${NC}"
if [ -d "$APP_DIR/backend/storage/app" ]; then
    tar -czf "$BACKUP_DIR/storage-$DATE.tar.gz" \
        -C "$APP_DIR/backend/storage" app 2>/dev/null
    
    if [ $? -eq 0 ]; then
        STORAGE_SIZE=$(du -h "$BACKUP_DIR/storage-$DATE.tar.gz" | cut -f1)
        echo -e "${GREEN}✓ Storage backup completed: storage-$DATE.tar.gz ($STORAGE_SIZE)${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Storage directory not found, skipping...${NC}"
fi

# 4. Backup Environment Files
echo -e "${YELLOW}[4/4] Backing up configuration files...${NC}"
mkdir -p "$BACKUP_DIR/configs"

if [ -f "$APP_DIR/backend/.env" ]; then
    cp "$APP_DIR/backend/.env" "$BACKUP_DIR/configs/backend.env-$DATE"
    echo -e "${GREEN}✓ Backend .env backed up${NC}"
fi

if [ -f "$APP_DIR/frontend/.env" ]; then
    cp "$APP_DIR/frontend/.env" "$BACKUP_DIR/configs/frontend.env-$DATE"
    echo -e "${GREEN}✓ Frontend .env backed up${NC}"
fi

if [ -f "/etc/nginx/sites-available/crm" ]; then
    cp /etc/nginx/sites-available/crm "$BACKUP_DIR/configs/nginx-$DATE.conf"
    echo -e "${GREEN}✓ Nginx config backed up${NC}"
fi

# Compress old backups older than 1 day if not already compressed
echo ""
echo -e "${YELLOW}Compressing old SQL backups...${NC}"
find "$BACKUP_DIR" -name "db-*.sql" -type f -mtime +1 -exec gzip {} \;

# Clean up old backups
echo -e "${YELLOW}Cleaning up backups older than $RETENTION_DAYS days...${NC}"
find "$BACKUP_DIR" -name "db-*.sql*" -type f -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "code-*.tar.gz" -type f -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "storage-*.tar.gz" -type f -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR/configs" -type f -mtime +$RETENTION_DAYS -delete

# Summary
echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Backup Summary${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "Date: $TODAY"
echo "Location: $BACKUP_DIR"
echo ""
echo "Current Backups:"
ls -lh "$BACKUP_DIR"/*.{sql,sql.gz,tar.gz} 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'
echo ""
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
echo "Total backup size: $TOTAL_SIZE"
echo ""

# Optional: Send notification or upload to cloud storage
# Uncomment and configure as needed:

# Send email notification (requires mailutils)
# echo "Backup completed successfully on $TODAY" | mail -s "FlowCRM Backup Success" admin@yourdomain.com

# Upload to S3 (requires aws-cli)
# aws s3 sync "$BACKUP_DIR" s3://your-bucket/crm-backups/

# Upload via rsync to remote server
# rsync -avz "$BACKUP_DIR/" user@remote-server:/remote/backup/path/

echo -e "${GREEN}✓ Backup completed successfully!${NC}"
echo ""
