@echo off
:: Run this file as Administrator (right-click → Run as administrator)
net session >nul 2>&1
if %errorLevel% neq 0 (
  echo Please right-click this file and choose "Run as administrator".
  pause
  exit /b 1
)

set HOSTS=%SystemRoot%\System32\drivers\etc\hosts
findstr /C:"signal-telegram.test" "%HOSTS%" >nul
if %errorLevel% neq 0 (
  echo.>>"%HOSTS%"
  echo 127.0.0.1 signal-telegram.test>>"%HOSTS%"
  echo Added signal-telegram.test to hosts.
) else (
  echo hosts entry already exists.
)

:: Reload Apache via Laragon httpd if available
for /d %%D in ("C:\laragon\bin\apache\httpd-*") do (
  if exist "%%D\bin\httpd.exe" (
    "%%D\bin\httpd.exe" -k restart
    echo Apache restarted.
    goto :done
  )
)
echo Could not find httpd.exe — please click Reload in Laragon.

:done
echo.
echo Open: http://signal-telegram.test/
pause
