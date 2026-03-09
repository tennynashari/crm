@echo off
echo ============================================
echo   CRM - Start All Services for ML Testing
echo ============================================
echo.

set "SCRIPT_DIR=%~dp0"
cd /d "%SCRIPT_DIR%"

echo Starting services in separate windows...
echo.

REM Start Laravel Backend
echo [1/3] Starting Laravel Backend...
start "Laravel Backend" cmd /k "cd backend && php artisan serve"
timeout /t 2 >nul

REM Start Python ML Service
echo [2/3] Starting Python ML Service...
start "Python ML Service" cmd /k "cd ml-service && venv\Scripts\activate && python run.py"
timeout /t 2 >nul

REM Start Vue Frontend
echo [3/3] Starting Vue Frontend...
start "Vue Frontend" cmd /k "cd frontend && npm run dev"
timeout /t 2 >nul

echo.
echo ============================================
echo   ✅ All Services Started!
echo ============================================
echo.
echo Services running:
echo   - Laravel Backend:    http://127.0.0.1:8000
echo   - Python ML Service:  http://127.0.0.1:5000
echo   - Vue Frontend:       http://localhost:5173
echo.
echo Check the opened windows for each service status.
echo.
echo To stop services: Close each window or press Ctrl+C
echo.
pause
