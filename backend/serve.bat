@echo off
cd /d "%~dp0"
echo Starting Laravel Server...
php artisan serve --host=localhost --port=8000
