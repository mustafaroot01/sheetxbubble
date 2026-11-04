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

echo "▶ Resetting OPcache (so updated PHP classes take effect)..."
# optimize:clear does NOT clear OPcache. PHP-FPM keeps the old compiled
# PHP until OPcache is reset, which makes new methods look "missing".
# Trigger a reset from inside FPM via a web hit, bypassing Cloudflare by
# resolving the domain straight to the local server (127.0.0.1).
DOMAIN=$(grep -E '^APP_URL=' .env | sed -E 's#^APP_URL=https?://([^/"]+).*#\1#')
case "$DOMAIN" in ''|localhost*|127.*) DOMAIN="diyala.org" ;; esac
echo "<?php if (function_exists('opcache_reset')) opcache_reset(); echo 'OPCACHE_OK';" > public/_oc.php
RESP=$(curl -sk --max-time 10 --resolve "${DOMAIN}:443:127.0.0.1" "https://${DOMAIN}/_oc.php" 2>/dev/null)
if ! echo "$RESP" | grep -q OPCACHE_OK; then
    # Fallback: hit the public URL directly (through Cloudflare) with a timeout
    RESP=$(curl -sk --max-time 10 "https://${DOMAIN}/_oc.php" 2>/dev/null)
fi
rm -f public/_oc.php
if echo "$RESP" | grep -q OPCACHE_OK; then
    echo "  OPcache reset OK"
else
    echo "  ⚠ Could not reset OPcache automatically."
    echo "    Run as root:  su -  then  systemctl restart plesk-php84-fpm"
fi

echo "✔ Done. The app now runs in production mode with full caching."
echo "  Note: after EVERY future git pull, run this script again."
echo "  If updated code still doesn't show, clear OPcache as root:"
echo "    su -  then  systemctl restart plesk-php84-fpm"
