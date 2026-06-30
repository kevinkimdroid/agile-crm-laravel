@echo off
REM One-time local setup for Agile CRM on Windows + XAMPP
setlocal
set "ROOT=%~dp0.."
set "PHP=C:\xampp\php\php.exe"
set "COMPOSER=C:\ProgramData\ComposerSetup\bin\composer.bat"
set "PATH=C:\xampp\php;C:\ProgramData\ComposerSetup\bin;C:\Program Files\nodejs;%PATH%"

cd /d "%ROOT%"
echo.
echo === Agile CRM local setup ===
echo Project: %CD%
echo.

if not exist "%PHP%" (echo ERROR: XAMPP PHP not found at %PHP% & exit /b 1)
if not exist "%COMPOSER%" (echo ERROR: Composer not found. Install from https://getcomposer.org/download/ & exit /b 1)

if not exist ".env" (
    echo Creating .env from .env.example...
    copy /Y .env.example .env >nul
)

if not exist "erp-clients-api\.env" (
    echo Creating erp-clients-api\.env ...
    copy /Y erp-clients-api\.env.example erp-clients-api\.env >nul
    echo   ^> Edit erp-clients-api\.env with Oracle credentials
)

echo.
echo PHP extensions (enable in C:\xampp\php\php.ini if composer fails):
echo   extension=gd
echo   extension=zip
echo.

echo [1/5] composer install...
call "%COMPOSER%" install --no-interaction --ignore-platform-req=ext-oci8
if errorlevel 1 (
    echo Retry with zip ignored...
    call "%COMPOSER%" install --no-interaction --ignore-platform-req=ext-oci8 --ignore-platform-req=ext-zip
)
if errorlevel 1 exit /b 1

echo [2/5] Application key...
"%PHP%" artisan key:generate --force

echo [3/5] Storage link...
"%PHP%" artisan storage:link 2>nul

where node >nul 2>&1
if %errorlevel% equ 0 (
    echo [4/5] npm install...
    call npm install
) else (
    echo [4/5] SKIP npm install — Node.js not in PATH. Install from https://nodejs.org/ then re-run.
)

where python >nul 2>&1
if %errorlevel% equ 0 (
    echo [5/5] ERP API Python deps...
    cd erp-clients-api
    python -m pip install -r requirements.txt -q
    cd ..
) else (
    echo [5/5] SKIP Python deps — install Python 3 from https://python.org/downloads/ ^(tick Add to PATH^)
)

echo.
echo === Setup done ===
echo.
echo NEXT: Edit .env with your MySQL/vtiger DB settings ^(DB_CONNECTION=vtiger^)
echo       Edit erp-clients-api\.env with Oracle credentials
echo.
echo START the app:
echo   Option A - XAMPP: Start Apache, open http://localhost/sites/agile-crm-laravel/public
echo                   Run scripts\start-local.bat for ERP API on port 5000
echo   Option B - Dev:  scripts\dev.bat  ^(needs Node + Python in PATH^)
echo.
pause
