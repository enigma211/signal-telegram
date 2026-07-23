@echo off
REM اجرای schedule لاراول هر دقیقه (لوکال Windows)
cd /d "%~dp0.."
set PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;%PATH%
echo Starting Laravel scheduler loop...
:loop
php artisan schedule:run --verbose --no-interaction
timeout /t 60 /nobreak >nul
goto loop
