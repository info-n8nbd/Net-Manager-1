#!/bin/bash

APP_DIR="/home/runner/workspace/artifacts/isp-billing"
PORT="${PORT:-8080}"

echo "============================================="
echo "  Softynex ISP Billing — Starting up..."
echo "============================================="

# ── Critical: Override Replit's DATABASE_URL which points to wrong host ──────
unset DATABASE_URL
unset DB_URL
export DATABASE_URL=""
export DB_URL=""

# ── PostgreSQL ──────────────────────────────────────────────────────────────
echo "[1/7] Setting up PostgreSQL..."
mkdir -p /home/runner/pgdata

if [ ! -f /home/runner/pgdata/PG_VERSION ]; then
  echo "  Initializing PostgreSQL data directory..."
  initdb -D /home/runner/pgdata --no-locale --encoding=UTF8 2>&1 | tail -3
fi

# Configure socket dir and listen address
grep -q "unix_socket_directories = '/tmp'" /home/runner/pgdata/postgresql.conf 2>/dev/null || \
  echo "unix_socket_directories = '/tmp'" >> /home/runner/pgdata/postgresql.conf
grep -q "^listen_addresses = '127.0.0.1'" /home/runner/pgdata/postgresql.conf 2>/dev/null || \
  echo "listen_addresses = '127.0.0.1'" >> /home/runner/pgdata/postgresql.conf

# Start PostgreSQL if not running
if ! pg_ctl -D /home/runner/pgdata status > /dev/null 2>&1; then
  pg_ctl -D /home/runner/pgdata -l /tmp/pg.log start > /dev/null 2>&1
  echo "  Waiting for PostgreSQL to be ready..."
  for i in $(seq 1 30); do
    if pg_isready -h /tmp -q 2>/dev/null; then break; fi
    sleep 1
  done
fi
echo "  PostgreSQL ready."

# Create user + database if not exists
PGUSER_EXISTS=$(psql -h /tmp -U runner -d postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname='ispuser'" 2>/dev/null)
if [ "$PGUSER_EXISTS" != "1" ]; then
  psql -h /tmp -U runner -d postgres -c "CREATE USER ispuser WITH PASSWORD 'ISP@Secure2024!';" 2>/dev/null
fi

DB_EXISTS=$(psql -h /tmp -U runner -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='ispdb'" 2>/dev/null)
if [ "$DB_EXISTS" != "1" ]; then
  psql -h /tmp -U runner -d postgres -c "CREATE DATABASE ispdb OWNER ispuser;" 2>/dev/null
  psql -h /tmp -U runner -d postgres -c "GRANT ALL PRIVILEGES ON DATABASE ispdb TO ispuser;" 2>/dev/null
fi
psql -h /tmp -U runner -d ispdb -c "GRANT ALL ON SCHEMA public TO ispuser;" 2>/dev/null || true
psql -h /tmp -U runner -d ispdb -c "ALTER DATABASE ispdb OWNER TO ispuser;" 2>/dev/null || true

# ── Redis ────────────────────────────────────────────────────────────────────
echo "[2/7] Starting Redis..."
if ! redis-cli -p 6379 ping > /dev/null 2>&1; then
  redis-server --daemonize yes --logfile /tmp/redis.log --port 6379 > /dev/null 2>&1
  sleep 1
fi
echo "  Redis ready."

# ── Laravel App Setup ────────────────────────────────────────────────────────
cd "$APP_DIR"

echo "[3/7] Configuring Laravel .env..."

# Determine APP_URL
if [ -n "$REPLIT_DEV_DOMAIN" ]; then
  RESOLVED_APP_URL="https://$REPLIT_DEV_DOMAIN"
else
  RESOLVED_APP_URL="http://0.0.0.0:$PORT"
fi

# Write .env — DATABASE_URL= overrides the system env var in Laravel's env() calls
cat > .env << ENVEOF
APP_NAME="SOFTYNEX"
APP_ENV=local
APP_KEY=base64:aqpbyhJRGl6j394SzjZRGYaVhcAIuuOMLF79HtjrEJQ=
APP_DEBUG=true
APP_URL=$RESOLVED_APP_URL
APP_TIMEZONE=Asia/Dhaka

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=/tmp
DB_PORT=5432
DB_DATABASE=ispdb
DB_USERNAME=ispuser
DB_PASSWORD=ISP@Secure2024!
DATABASE_URL=

CACHE_DRIVER=redis
SESSION_DRIVER=file
QUEUE_CONNECTION=database

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=hivarjom@gmail.com
MAIL_PASSWORD=iztnptvxrjlidvwi
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hivarjom@gmail.com
MAIL_FROM_NAME="SOFTYNEX"

OPENCAGE_API_KEY=37e22662c7614c5996dacf00d6279b95
SESSION_LIFETIME=30
PAYMENT_DEMO_MODE=false
ENVEOF

echo "  .env configured."

# ── Storage & Cache Directories ──────────────────────────────────────────────
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/app/public bootstrap/cache 2>/dev/null
chmod -R 775 storage bootstrap/cache 2>/dev/null

php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

php artisan storage:link 2>/dev/null || true

# ── Database: Restore or Migrate ─────────────────────────────────────────────
echo "[4/7] Setting up database..."
TABLE_COUNT=$(PGPASSWORD="ISP@Secure2024!" psql -h /tmp -U ispuser -d ispdb \
  -tAc "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE';" 2>/dev/null | tr -d '[:space:]')

if [ -z "$TABLE_COUNT" ] || [ "$TABLE_COUNT" -lt 5 ]; then
  if [ -f "database/full_backup.sql" ]; then
    echo "  Restoring database from backup (this may take a moment)..."
    PGPASSWORD="ISP@Secure2024!" psql -h /tmp -U ispuser -d ispdb -f database/full_backup.sql > /tmp/db_restore.log 2>&1
    echo "  Database restored."
  else
    echo "  Running migrations + seeders..."
    php artisan migrate --seed --force 2>&1 | tail -20
  fi
fi

# Mark any pending migrations as completed (they exist in the imported DB schema)
echo "  Syncing migration records..."
PENDING_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Pending" | tr -d '[:space:]')
PENDING_COUNT="${PENDING_COUNT:-0}"
if [ "${PENDING_COUNT}" -gt 0 ] 2>/dev/null; then
  NEW_BATCH=$(PGPASSWORD="ISP@Secure2024!" psql -h /tmp -U ispuser -d ispdb -tAc "SELECT COALESCE(MAX(batch),0)+1 FROM migrations;" 2>/dev/null | tr -d '[:space:]')
  NEW_BATCH="${NEW_BATCH:-99}"
  php artisan migrate:status 2>/dev/null | grep "Pending" | grep -oE "[0-9]{4}_[0-9_a-z]+" | while read mig; do
    EXISTS=$(PGPASSWORD="ISP@Secure2024!" psql -h /tmp -U ispuser -d ispdb -tAc "SELECT COUNT(*) FROM migrations WHERE migration='$mig';" 2>/dev/null | tr -d '[:space:]')
    if [ "$EXISTS" = "0" ]; then
      PGPASSWORD="ISP@Secure2024!" psql -h /tmp -U ispuser -d ispdb -c \
        "INSERT INTO migrations (migration, batch) VALUES ('$mig', $NEW_BATCH);" 2>/dev/null || true
    fi
  done
  echo "  Migration records synced."
fi

# Post-setup artisan command if it exists
php artisan app:post-setup 2>/dev/null || true

# ── Optimize ─────────────────────────────────────────────────────────────────
echo "[5/7] Optimizing..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# ── Queue Worker (background) ────────────────────────────────────────────────
echo "[6/7] Starting queue worker..."
php artisan queue:work --queue=default,high --tries=2 --timeout=60 \
  --sleep=3 --max-jobs=1000 --max-time=3600 > /tmp/queue.log 2>&1 &
echo "  Queue worker started."

# ── Scheduler (background) ───────────────────────────────────────────────────
echo "[7/7] Starting scheduler & server..."
php artisan schedule:work > /tmp/scheduler.log 2>&1 &

echo "============================================="
echo "  Softynex ISP Billing is READY!"
echo "  URL: $RESOLVED_APP_URL"
echo "  Login: superadmin@isp.com / SuperAdmin@123"
echo "============================================="

exec php artisan serve --host=0.0.0.0 --port="$PORT"
