#!/bin/bash
# ============================================================
#  SheetxBubble - Quick Update + Production Optimize
#  Run on the server from the app root:
#    bash server-update.sh
# ============================================================
set -e

echo "▶ Pulling latest code..."
git pull origin main

echo "▶ Switching .env to production mode..."
if grep -q '^APP_ENV=' .env; then
    sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
else
    echo 'APP_ENV=production' >> .env
fi
if grep -q '^APP_DEBUG=' .env; then
    sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
else
    echo 'APP_DEBUG=false' >> .env
fi

echo "▶ Installing dependencies (optimized autoloader)..."
composer install --no-dev --optimize-autoloader 2>/dev/null || echo "  (composer skipped — run manually if needed)"

echo "▶ Clearing old caches..."
php artisan optimize:clear

echo "▶ Building production caches..."
php artisan optimize
php artisan filament:optimize 2>/dev/null || true
php artisan icons:cache 2>/dev/null || true

echo "✔ Done. The app now runs in production mode with full caching."
echo "  Note: after EVERY future git pull, run this script again"
echo "  (cached config/routes/views must be rebuilt)."
