@echo off
cd /d "%~dp0"
echo Optimizing Agile Craft CRM...

echo [1/4] Optimizing Composer autoloader...
call composer dump-autoload --optimize --no-interaction

echo [2/4] Caching Blade views...
php artisan view:cache

echo [3/4] Caching events...
php artisan event:cache

echo [4/4] Caching routes...
php artisan route:cache

REM NOTE: "php artisan config:cache" is intentionally NOT run.
REM This app reads several values with env() directly in application code
REM (e.g. PBX_AMI_* in PbxController), and env() returns null once config
REM is cached. Caching config would break those features.

echo.
echo Done. To reverse: php artisan optimize:clear  (then composer dump-autoload)
pause
