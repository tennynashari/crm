@echo off
echo ========================================
echo FlowCRM - Setup Script
echo ========================================
echo.

echo [1/6] Checking prerequisites...
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Composer not found. Please install Composer first.
    pause
    exit /b 1
)

where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP not found. Please install PHP first.
    pause
    exit /b 1
)

where node >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Node.js not found. Please install Node.js first.
    pause
    exit /b 1
)

echo Prerequisites check passed!
echo.

echo [2/6] Setting up database...
echo Please make sure PostgreSQL is running and you have created:
echo - Database: crm
echo - User: crm with password: crm
echo.
pause

echo [3/6] Installing backend dependencies...
cd backend
call composer install
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to install backend dependencies
    pause
    exit /b 1
)

echo [4/6] Setting up backend...
if not exist .env (
    copy .env.example .env
)
call php artisan key:generate
call php artisan migrate --force
call php artisan db:seed --force
echo Backend setup complete!
echo.

echo [5/6] Installing frontend dependencies...
cd ..\frontend
call npm install
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to install frontend dependencies
    pause
    exit /b 1
)
echo Frontend dependencies installed!
echo.

echo [6/6] Setup complete!
echo.
echo ========================================
echo Next steps:
echo ========================================
echo 1. Start backend:  cd backend ^&^& php artisan serve
echo 2. Start frontend: cd frontend ^&^& npm run dev
echo 3. Open browser:   http://localhost:5173
echo 4. Login with:     admin@flowcrm.test / password
echo ========================================
echo.
pause
