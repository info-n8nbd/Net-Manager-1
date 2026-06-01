#!/bin/bash

cd /home/runner/workspace/isp-app

# Production port — Replit passes PORT env var
SERVE_PORT="${PORT:-8099}"

# Parse DATABASE_URL if PGHOST is not set
if [ -n "$DATABASE_URL" ] && [ -z "$PGHOST" ]; then
  export PGHOST=$(echo "$DATABASE_URL" | sed -n 's|.*@\([^:/]*\).*|\1|p')
  export PGPORT=$(echo "$DATABASE_URL" | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
  export PGDATABASE=$(echo "$DATABASE_URL" | sed -n 's|.*/\([^?]*\).*|\1|p')
  export PGUSER=$(echo "$DATABASE_URL" | sed -n 's|.*://\([^:]*\):.*|\1|p')
  export PGPASSWORD=$(echo "$DATABASE_URL" | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')
fi

if [ -z "$PGPASSWORD" ] && [ -n "$PGHOST" ]; then
  export PGPASSWORD="${PGPASSWORD:-password}"
fi

echo "============================================="
echo "  Smart Online ISP — Production Starting..."
echo "  Port: $SERVE_PORT"
echo "============================================="

# ── Wait for PostgreSQL ───────────────────────────────────────────────────────
echo "Waiting for PostgreSQL..."
for i in $(seq 1 60); do
  if psql -h "${PGHOST:-127.0.0.1}" -p "${PGPORT:-5432}" -U "${PGUSER:-postgres}" -d "${PGDATABASE:-smart_isp}" \
    -c "SELECT 1;" >/dev/null 2>&1; then
    echo "PostgreSQL is ready."
    break
  fi
  if [ "$i" -eq 60 ]; then
    echo "WARNING: PostgreSQL not responding — continuing anyway..."
  fi
  sleep 1
done

# ── Install Composer deps if missing ─────────────────────────────────────────
if [ ! -f "vendor/autoload.php" ]; then
  echo "Installing Composer dependencies..."
  composer install --no-interaction --no-dev --optimize-autoloader 2>&1
fi

# ── Determine APP_URL ─────────────────────────────────────────────────────────
if [ -n "$REPLIT_DOMAINS" ]; then
  FIRST_DOMAIN=$(echo "$REPLIT_DOMAINS" | cut -d',' -f1)
  RESOLVED_APP_URL="https://$FIRST_DOMAIN"
elif [ -n "$REPLIT_DEV_DOMAIN" ]; then
  RESOLVED_APP_URL="https://$REPLIT_DEV_DOMAIN"
else
  RESOLVED_APP_URL="http://0.0.0.0:$SERVE_PORT"
fi

# ── Preserve existing APP_KEY ─────────────────────────────────────────────────
if [ -z "$APP_KEY" ] && [ -f .env ]; then
  EXISTING_APP_KEY=$(grep '^APP_KEY=' .env | cut -d'=' -f2-)
fi

# ── Generate .env ─────────────────────────────────────────────────────────────
cat > .env << ENVEOF
APP_NAME="${APP_NAME:-Smart Online ISP}"
APP_ENV=production
APP_KEY=${APP_KEY:-${EXISTING_APP_KEY:-}}
APP_DEBUG=false
APP_TIMEZONE=${APP_TIMEZONE:-Asia/Dhaka}
APP_URL=$RESOLVED_APP_URL

APP_LOCALE=${APP_LOCALE:-en}
APP_FALLBACK_LOCALE=${APP_FALLBACK_LOCALE:-en}
APP_FAKER_LOCALE=${APP_FAKER_LOCALE:-en_US}
APP_MAINTENANCE_DRIVER=${APP_MAINTENANCE_DRIVER:-file}
PHP_CLI_SERVER_WORKERS=${PHP_CLI_SERVER_WORKERS:-8}

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

SESSION_DRIVER=database
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
SESSION_ENCRYPT=${SESSION_ENCRYPT:-false}
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-log}
FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
CACHE_STORE=${CACHE_STORE:-file}
CACHE_PREFIX=${CACHE_PREFIX:-isp_}

MAIL_MAILER=${MAIL_MAILER:-log}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@isp.com}
MAIL_FROM_NAME="${APP_NAME:-Smart Online ISP}"

SMS_ENABLED=${SMS_ENABLED:-false}

TRUSTED_PROXIES=${TRUSTED_PROXIES:-*}
ENVEOF

# Setup directories
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public bootstrap/cache 2>/dev/null
chmod -R 775 storage bootstrap/cache 2>/dev/null

# Generate APP_KEY if missing
if ! grep -q "APP_KEY=base64:" .env; then
  php artisan key:generate --force
fi

# Publish vendor views if missing
if [ ! -f "resources/views/vendor/pagination/bootstrap-4.blade.php" ]; then
  php artisan vendor:publish --tag=laravel-pagination --force 2>/dev/null
fi

# Create storage symlink
php artisan storage:link 2>/dev/null

# ── Database setup ────────────────────────────────────────────────────────────
TABLE_COUNT=$(psql -h "${PGHOST:-127.0.0.1}" -p "${PGPORT:-5432}" -U "${PGUSER:-postgres}" -d "${PGDATABASE:-smart_isp}" \
  -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE';" 2>/dev/null | tr -d '[:space:]')

if [ -z "$TABLE_COUNT" ] || [ "$TABLE_COUNT" -lt 5 ]; then
  if [ -f "database/full_backup.sql" ]; then
    echo "Fresh database — restoring from backup..."
    psql -h "${PGHOST:-127.0.0.1}" -p "${PGPORT:-5432}" -U "${PGUSER:-postgres}" -d "${PGDATABASE:-smart_isp}" \
      -f database/full_backup.sql 2>&1 | tail -5
    RESTORE_CHECK=$(psql -h "${PGHOST:-127.0.0.1}" -p "${PGPORT:-5432}" -U "${PGUSER:-postgres}" -d "${PGDATABASE:-smart_isp}" \
      -t -c "SELECT COUNT(*) FROM users;" 2>/dev/null | tr -d '[:space:]')
    if [ -z "$RESTORE_CHECK" ] || [ "$RESTORE_CHECK" = "0" ]; then
      echo "Backup may have failed — running migrations..."
      php artisan migrate --seed --force 2>&1
    else
      echo "Restored ($RESTORE_CHECK users)."
    fi
  else
    php artisan migrate --seed --force 2>&1
  fi
else
  echo "Database exists ($TABLE_COUNT tables) — running pending migrations..."
  php artisan migrate --force 2>&1
fi

# Post-setup fixes
php artisan app:post-setup 2>&1

# Cache config + routes for production performance
php artisan config:cache 2>/dev/null
php artisan route:cache 2>/dev/null

# Start scheduler in background
php artisan schedule:work &

echo "============================================="
echo "  Smart Online ISP is ready! (Production)"
echo "  Server: http://0.0.0.0:$SERVE_PORT"
echo "  Login: superadmin@isp.com / SuperAdmin@123"
echo "============================================="

# Serve on the production port
php artisan serve --host=0.0.0.0 --port=$SERVE_PORT --no-reload
