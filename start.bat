@echo off
echo Starting FlowCRM...
echo.

echo Starting Backend Server...
start "FlowCRM Backend" cmd /k "cd backend && php artisan serve"

timeout /t 3 /nobreak >nul

echo Starting Frontend Server...
start "FlowCRM Frontend" cmd /k "cd frontend && npm run dev"

echo.
echo ========================================
echo FlowCRM is starting...
echo ========================================
echo Backend:  http://localhost:8000
echo Frontend: http://localhost:5173
echo.
echo Login: admin@flowcrm.test / password
echo ========================================
echo.
echo Press any key to stop all servers...
pause >nul

taskkill /FI "WindowTitle eq FlowCRM Backend*" /T /F
taskkill /FI "WindowTitle eq FlowCRM Frontend*" /T /F
