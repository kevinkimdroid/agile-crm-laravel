@echo off
cd /d "%~dp0"
echo Kenya Orient erp-clients-api — after git pull, restart so new routes (e.g. investment-maturities) load.
set PYEXE=py -3
%PYEXE% --version >nul 2>&1 || set PYEXE=python
%PYEXE% --version >nul 2>&1 || (
    echo Python not found. Install Python 3 and run: pip install -r requirements.txt
    exit /b 1
)
%PYEXE% -m pip show flask >nul 2>&1 || %PYEXE% -m pip install -r requirements.txt
%PYEXE% app.py
