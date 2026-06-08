#!/bin/bash
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-payrello}"
DB_PASS="${DB_PASS:-payrello_secret}"

echo "[entrypoint] Waiting for database at $DB_HOST:$DB_PORT..."

# Wait until MySQL is ready to accept connections
MAX_TRIES=60
TRIES=0
until mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" --ssl=0 -e "SELECT 1" &>/dev/null; do
    TRIES=$((TRIES + 1))
    if [ "$TRIES" -ge "$MAX_TRIES" ]; then
        echo "[entrypoint] ERROR: Database did not become ready after $MAX_TRIES attempts."
        exit 1
    fi
    echo "[entrypoint] Database not ready yet (attempt $TRIES/$MAX_TRIES)..."
    sleep 2
done

echo "[entrypoint] Database is ready."

# Run setup.php — it checks internally whether setup is needed
php /var/www/html/docker/setup.php

# Ensure correct ownership for runtime-writable paths
chown -R www-data:www-data /var/www/html/pp-media
chmod -R 775 /var/www/html/pp-media

# Hand off to Apache
echo "[entrypoint] Starting Apache..."
exec apache2-foreground
