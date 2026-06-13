#!/bin/bash
# Production deployment script for Geminia CRM
# Run this on the server after uploading code and before switching traffic

set -e

echo "=== Geminia CRM - Production Deploy ==="

# 1. Install production dependencies (no dev packages)
echo "[1/7] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Build frontend assets (Vite)
echo "[2/7] Building frontend assets..."
npm ci --omit=dev
npm run build

# 3. Ensure storage directories exist and are writable by the web server (file cache)
echo "[3/8] Ensuring storage directories and permissions..."
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
if id www-data &>/dev/null; then
  WEB_USER=www-data
elif id apache &>/dev/null; then
  WEB_USER=apache
elif id nginx &>/dev/null; then
  WEB_USER=nginx
else
  WEB_USER=$(whoami)
fi
if [ "$(id -u)" -eq 0 ]; then
  chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache
else
  sudo chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache 2>/dev/null || true
fi
chmod -R ug+rwx storage bootstrap/cache
find storage/framework/cache/data -type d -exec chmod ug+rwx {} + 2>/dev/null || true

# 4. Clear all caches first (in case of stale cached config)
echo "[4/8] Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 5. Run migrations (if any)
echo "[5/8] Running migrations..."
php artisan migrate --force --no-interaction || true

# 6. Create production caches (faster response)
echo "[6/8] Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Storage link (if not exists)
echo "[7/8] Ensuring storage link..."
php artisan storage:link 2>/dev/null || true

# 8. Permissions reminder
echo "[8/8] Done!"
echo ""
echo "REMINDER: storage/ and bootstrap/cache/ must be owned by the web server user (${WEB_USER})."
echo "If file cache errors persist, set CACHE_STORE=database in .env and run: php artisan config:cache"
echo ""
echo "Check .env has: APP_ENV=production, APP_DEBUG=false, APP_URL=<your-domain>"
