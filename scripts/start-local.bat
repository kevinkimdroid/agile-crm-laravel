@echo off
REM XAMPP local stack: ERP API + optional queue worker (Apache serves the site)
set "ROOT=%~dp0.."
set "PHP=C:\xampp\php\php.exe"
set "PATH=C:\xampp\php;C:\Program Files\nodejs;C:\ProgramData\ComposerSetup\bin;%PATH%"

cd /d "%ROOT%"

echo.
echo === Agile CRM — local stack ===
echo.
echo 1) Start Apache in XAMPP Control Panel if not running
echo 2) Open: http://localhost/sites/agile-crm-laravel/public
echo.

where python >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python not in PATH. Install Python 3 and tick "Add to PATH"
    echo        Or run erp-clients-api manually from that folder.
    pause
    exit /b 1
)

start "ERP API :5000" cmd /k "cd /d %ROOT%\erp-clients-api && python app.py"
timeout /t 3 /nobreak >nul

echo ERP API starting on http://127.0.0.1:5000
curl -s http://127.0.0.1:5000/health 2>nul || echo   ^(wait a few seconds, then test health URL^)

start "Queue worker" cmd /k "cd /d %ROOT% && %PHP% artisan queue:work --tries=3"

echo.
echo Done. Keep both windows open.
pause
