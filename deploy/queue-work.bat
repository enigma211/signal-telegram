@echo off
REM Redis queues via artisan (Horizon needs pcntl — Linux only)
cd /d "%~dp0.."
set PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;%PATH%
start "queue-fa" cmd /k php artisan queue:work redis --queue=telegram-fa,default --sleep=3 --tries=3 --timeout=600
start "queue-en" cmd /k php artisan queue:work redis --queue=telegram-en,default --sleep=3 --tries=3 --timeout=600
echo Redis workers FA/EN started. On Linux use: php artisan horizon
pause
