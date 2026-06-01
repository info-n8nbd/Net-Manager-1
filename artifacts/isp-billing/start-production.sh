#!/bin/bash

echo "============================================"
echo "  ISP Billing System - Production Server"
echo "  $(date '+%Y-%m-%d %H:%M:%S %Z')"
echo "============================================"

PROJECT_DIR="${PROJECT_DIR:-$(cd "$(dirname "$0")" && pwd)}"
cd "$PROJECT_DIR"

PORT="${PORT:-5000}"

mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public storage/app/backups storage/app/imports storage/app/exports
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

if [ ! -L "public/storage" ]; then
    php artisan storage:link --force 2>/dev/null || ln -sf ../storage/app/public public/storage 2>/dev/null || true
fi

if [ -n "$DATABASE_URL" ] && [ -z "$PGHOST" ]; then
    export PGHOST=$(echo "$DATABASE_URL" | sed -n 's|.*@\([^:/]*\).*|\1|p')
    export PGPORT=$(echo "$DATABASE_URL" | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
    export PGDATABASE=$(echo "$DATABASE_URL" | sed -n 's|.*/\([^?]*\).*|\1|p')
    export PGUSER=$(echo "$DATABASE_URL" | sed -n 's|.*://\([^:]*\):.*|\1|p')
    export PGPASSWORD=$(echo "$DATABASE_URL" | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')
fi

if [ -n "$REPLIT_DEV_DOMAIN" ]; then
    RESOLVED_APP_URL="https://$REPLIT_DEV_DOMAIN"
elif [ -n "$REPLIT_DOMAINS" ]; then
    FIRST_DOMAIN=$(echo "$REPLIT_DOMAINS" | cut -d',' -f1)
    RESOLVED_APP_URL="https://$FIRST_DOMAIN"
else
    RESOLVED_APP_URL="http://0.0.0.0:$PORT"
fi

if [ ! -f ".env" ] || [ -n "$REPLIT_DEV_DOMAIN" ] || [ -n "$REPLIT_DOMAINS" ]; then
    cat > .env << ENVEOF
APP_NAME="${APP_NAME:-Smart Online ISP}"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_TIMEZONE=${APP_TIMEZONE:-Asia/Dhaka}
APP_URL=$RESOLVED_APP_URL

APP_LOCALE=${APP_LOCALE:-en}
APP_FALLBACK_LOCALE=${APP_FALLBACK_LOCALE:-en}
APP_FAKER_LOCALE=${APP_FAKER_LOCALE:-en_US}
APP_MAINTENANCE_DRIVER=${APP_MAINTENANCE_DRIVER:-file}
PHP_CLI_SERVER_WORKERS=${PHP_CLI_SERVER_WORKERS:-4}

BCRYPT_ROUNDS=${BCRYPT_ROUNDS:-12}

LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=${LOG_STACK:-single}
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${PGHOST:-127.0.0.1}
DB_PORT=${PGPORT:-5432}
DB_DATABASE=${PGDATABASE:-smart_isp}
DB_USERNAME=${PGUSER:-postgres}
DB_PASSWORD=${PGPASSWORD:-}

SESSION_DRIVER=${SESSION_DRIVER:-database}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
SESSION_ENCRYPT=${SESSION_ENCRYPT:-false}
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-log}
FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}
CACHE_STORE=${CACHE_STORE:-database}
CACHE_PREFIX=${CACHE_PREFIX:-isp_}

MAIL_MAILER=${MAIL_MAILER:-log}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@isp.com}
MAIL_FROM_NAME="${APP_NAME:-Smart Online ISP}"

SMS_ENABLED=${SMS_ENABLED:-false}

TRUSTED_PROXIES=${TRUSTED_PROXIES:-*}
ENVEOF

    if ! grep -q "APP_KEY=base64:" .env; then
        php artisan key:generate --force 2>/dev/null
    fi
fi

echo "[1/4] Starting production server on port $PORT (immediately)..."
php artisan serve --host=0.0.0.0 --port="$PORT" --no-reload 2>&1 &
SERVER_PID=$!
echo "  -> Server started (PID: $SERVER_PID)"

sleep 2

echo "[2/4] Running database migrations & post-setup..."
php artisan migrate --force 2>&1 || echo "  -> Migrations check completed"
php artisan app:post-setup 2>&1 || echo "  -> Post-setup check completed"

echo "[3/4] Optimizing for production..."
php artisan permission:cache-reset 2>/dev/null || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "[4/4] Starting background services..."

php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --quiet &
QUEUE_PID=$!
echo "  -> Queue worker started (PID: $QUEUE_PID)"

php artisan schedule:work --quiet &
SCHEDULER_PID=$!
echo "  -> Task scheduler started (PID: $SCHEDULER_PID)"

cleanup() {
    echo ""
    echo "Shutting down ISP Billing System..."
    kill "$SERVER_PID" 2>/dev/null || true
    kill "$QUEUE_PID" 2>/dev/null || true
    kill "$SCHEDULER_PID" 2>/dev/null || true
    echo "Shutdown complete."
    exit 0
}
trap cleanup SIGTERM SIGINT

echo ""
echo "============================================"
echo "  Server is ready!"
echo "  URL: http://0.0.0.0:$PORT"
echo "  Queue Worker: Running"
echo "  Scheduler: Running"
echo "============================================"
echo ""

wait $SERVER_PID
