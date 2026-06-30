@echo off
REM Full dev stack via composer (needs Node.js + Python in PATH)
set "ROOT=%~dp0.."
set "PATH=C:\xampp\php;C:\ProgramData\ComposerSetup\bin;C:\Program Files\nodejs;%PATH%"
cd /d "%ROOT%"

where composer >nul 2>&1 || set "PATH=C:\ProgramData\ComposerSetup\bin;%PATH%"
if not exist "vendor\autoload.php" (
    echo Run scripts\setup-local.bat first.
    exit /b 1
)

where npx >nul 2>&1
if errorlevel 1 (
    echo.
    echo npx not found — Node.js is missing or not in PATH.
    echo Install LTS from https://nodejs.org/ ^(tick "Add to PATH"^), restart Cursor, then run npm install.
    echo.
    echo Starting without npx instead ^(separate windows^)...
    call "%~dp0dev-windows.bat"
    exit /b %errorlevel%
)

where python >nul 2>&1 || (
    echo WARNING: Python 3 not in PATH — ERP API on port 5000 will not start.
    echo Install from https://python.org/downloads/ and tick Add to PATH.
)

composer run dev
