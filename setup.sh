#!/bin/bash

echo "========================================"
echo "FlowCRM - Setup Script"
echo "========================================"
echo ""

echo "[1/6] Checking prerequisites..."
if ! command -v composer &> /dev/null; then
    echo "ERROR: Composer not found. Please install Composer first."
    exit 1
fi

if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found. Please install PHP first."
    exit 1
fi

if ! command -v node &> /dev/null; then
    echo "ERROR: Node.js not found. Please install Node.js first."
    exit 1
fi

echo "Prerequisites check passed!"
echo ""

echo "[2/6] Setting up database..."
echo "Please make sure PostgreSQL is running and you have created:"
echo "- Database: crm"
echo "- User: crm with password: crm"
echo ""
read -p "Press enter to continue..."

echo "[3/6] Installing backend dependencies..."
cd backend
composer install
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to install backend dependencies"
    exit 1
fi

echo "[4/6] Setting up backend..."
if [ ! -f .env ]; then
    cp .env.example .env
fi
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
echo "Backend setup complete!"
echo ""

echo "[5/6] Installing frontend dependencies..."
cd ../frontend
npm install
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to install frontend dependencies"
    exit 1
fi
echo "Frontend dependencies installed!"
echo ""

echo "[6/6] Setup complete!"
echo ""
echo "========================================"
echo "Next steps:"
echo "========================================"
echo "1. Start backend:  cd backend && php artisan serve"
echo "2. Start frontend: cd frontend && npm run dev"
echo "3. Open browser:   http://localhost:5173"
echo "4. Login with:     admin@flowcrm.test / password"
echo "========================================"
echo ""
