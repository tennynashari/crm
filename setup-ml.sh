#!/bin/bash

echo "========================================"
echo "  CRM ML Service - Quick Setup"
echo "========================================"
echo ""

cd ml-service

echo "[1/4] Creating Python virtual environment..."
python3 -m venv venv
if [ $? -ne 0 ]; then
    echo "Error: Failed to create virtual environment"
    exit 1
fi

echo ""
echo "[2/4] Activating virtual environment..."
source venv/bin/activate

echo ""
echo "[3/4] Installing dependencies..."
pip install -r requirements.txt
if [ $? -ne 0 ]; then
    echo "Error: Failed to install dependencies"
    exit 1
fi

echo ""
echo "[4/4] Checking .env configuration..."
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
    echo ""
    echo "⚠️ IMPORTANT: Please edit ml-service/.env and configure your database settings!"
    echo ""
fi

echo ""
echo "========================================"
echo "  ✅ Setup Complete!"
echo "========================================"
echo ""
echo "To start the ML service:"
echo "  1. cd ml-service"
echo "  2. source venv/bin/activate"
echo "  3. python run.py"
echo ""
echo "Then configure Laravel .env:"
echo "  ML_SERVICE_URL=http://127.0.0.1:5000"
echo ""
