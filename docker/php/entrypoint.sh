#!/bin/sh
# Entrypoint container "app" (PHP-FPM).
set -eu

APP_DIR="${APP_DIR:-/var/www/html}"

# 1. Konfigurasi aplikasi dari environment (.env -> docker-compose -> di sini).
#    Set APP_CONFIG_SYNC=false kalau ingin config/config.php diatur manual.
if [ "${APP_CONFIG_SYNC:-true}" = "true" ]; then
    php /usr/local/bin/generate-config.php
else
    echo "[entrypoint] APP_CONFIG_SYNC=false, config/config.php dibiarkan apa adanya"
fi

# 2. Pastikan folder runtime bisa ditulis PHP-FPM (www-data).
mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/storage/sessions"
chown -R www-data:www-data "$APP_DIR/storage" 2>/dev/null || true

# 3. Jalankan php-fpm (master sebagai root, worker otomatis turun ke www-data).
exec "$@"
