@echo off
REM Start dev stack in separate windows (no npx/concurrently). Needs Python for ERP API.
setlocal
set "ROOT=%~dp0.."
set "PHP=C:\xampp\php\php.exe"
set "PATH=C:\xampp\php;C:\ProgramData\ComposerSetup\bin;C:\Program Files\nodejs;%PATH%"
cd /d "%ROOT%"

if not exist "vendor\autoload.php" (
    echo Run scripts\setup-local.bat first.
    exit /b 1
)

echo.
echo === Agile CRM dev stack (Windows) ===
echo.

start "CRM server :8000" cmd /k "cd /d %ROOT% && %PHP% artisan serve"
start "Queue worker" cmd /k "cd /d %ROOT% && %PHP% artisan queue:listen --tries=1 --timeout=660"
start "Scheduler" cmd /k "cd /d %ROOT% && %PHP% artisan schedule:work"

where node >nul 2>&1
if %errorlevel% equ 0 (
    if exist "node_modules\vite\bin\vite.js" (
        start "Vite" cmd /k "cd /d %ROOT% && npm run dev"
    ) else (
        echo Vite skipped — run: npm install
    )
) else (
    echo Vite skipped — install Node.js from https://nodejs.org/ then npm install
)

where python >nul 2>&1
if %errorlevel% equ 0 (
    start "ERP API :5000" cmd /k "cd /d %ROOT%\erp-clients-api && python app.py"
) else (
    echo ERP API skipped — install Python 3 and add to PATH
)

echo.
echo Open http://localhost:8000
echo Keep the opened terminal windows running.
echo.
pause
