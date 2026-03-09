@echo off
echo ========================================
echo   CRM ML Service - Quick Setup
echo ========================================
echo.

cd ml-service

echo [1/4] Creating Python virtual environment...
python -m venv venv
if errorlevel 1 (
    echo Error: Failed to create virtual environment
    pause
    exit /b 1
)

echo.
echo [2/4] Activating virtual environment...
call venv\Scripts\activate.bat

echo.
echo [3/4] Installing dependencies...
pip install -r requirements.txt
if errorlevel 1 (
    echo Error: Failed to install dependencies
    pause
    exit /b 1
)

echo.
echo [4/4] Checking .env configuration...
if not exist .env (
    echo Creating .env from .env.example...
    copy .env.example .env
    echo.
    echo ⚠️ IMPORTANT: Please edit ml-service\.env and configure your database settings!
    echo.
)

echo.
echo ========================================
echo   ✅ Setup Complete!
echo ========================================
echo.
echo To start the ML service:
echo   1. cd ml-service
echo   2. venv\Scripts\activate
echo   3. python run.py
echo.
echo Then configure Laravel .env:
echo   ML_SERVICE_URL=http://127.0.0.1:5000
echo.
pause
