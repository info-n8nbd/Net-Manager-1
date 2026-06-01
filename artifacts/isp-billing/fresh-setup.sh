#!/bin/bash
set -e

echo "=============================================="
echo "  Smart Online ISP - Fresh Setup Script"
echo "=============================================="
echo ""

cd /home/runner/workspace/isp-app

# ── Step 1: Parse DATABASE_URL ────────────────────────────────────────────────
echo "[1/8] Parsing database connection..."
if [ -n "$DATABASE_URL" ] && [ -z "$PGHOST" ]; then
  export PGHOST=$(echo "$DATABASE_URL" | sed -n 's|.*@\([^:/]*\).*|\1|p')
  export PGPORT=$(echo "$DATABASE_URL" | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
  export PGDATABASE=$(echo "$DATABASE_URL" | sed -n 's|.*/\([^?]*\).*|\1|p')
  export PGUSER=$(echo "$DATABASE_URL" | sed -n 's|.*://\([^:]*\):.*|\1|p')
  export PGPASSWORD=$(echo "$DATABASE_URL" | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')
fi

if [ -z "$PGHOST" ]; then
  echo "  ERROR: No PostgreSQL connection found."
  echo "  Please add a PostgreSQL database to this Repl first."
  echo "  Go to: Tools > Database > Create PostgreSQL"
  exit 1
fi
echo "  ✓ Database: $PGDATABASE@$PGHOST:$PGPORT"

# ── Step 2: Install Composer dependencies ─────────────────────────────────────
echo ""
echo "[2/8] Installing PHP/Composer dependencies..."
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
  composer install --no-interaction --no-dev --optimize-autoloader 2>&1
  echo "  ✓ Composer dependencies installed"
else
  echo "  ✓ Composer vendor/ already exists, running update..."
  composer install --no-interaction --no-dev --optimize-autoloader 2>&1
fi

# ── Step 3: Install pnpm dependencies ─────────────────────────────────────────
echo ""
echo "[3/8] Installing Node.js/pnpm dependencies..."
cd /home/runner/workspace

if [ ! -f "pnpm-lock.yaml" ]; then
  echo "  WARNING: pnpm-lock.yaml not found, running pnpm install..."
  pnpm install 2>&1
else
  pnpm install --frozen-lockfile 2>&1 || pnpm install 2>&1
fi
echo "  ✓ pnpm dependencies installed"
cd /home/runner/workspace/isp-app

# ── Step 4: Setup directories and permissions ─────────────────────────────────
echo ""
echo "[4/8] Setting up directories and permissions..."
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public bootstrap/cache 2>/dev/null
chmod -R 775 storage bootstrap/cache 2>/dev/null
echo "  ✓ Storage directories ready"

# ── Step 5: Generate .env file ────────────────────────────────────────────────
echo ""
echo "[5/8] Generating .env configuration..."
if [ -n "$REPLIT_DEV_DOMAIN" ]; then
  RESOLVED_APP_URL="https://$REPLIT_DEV_DOMAIN"
elif [ -n "$REPLIT_DOMAINS" ]; then
  FIRST_DOMAIN=$(echo "$REPLIT_DOMAINS" | cut -d',' -f1)
  RESOLVED_APP_URL="https://$FIRST_DOMAIN"
else
  RESOLVED_APP_URL="http://0.0.0.0:5000"
fi

if [ -f .env ]; then
  EXISTING_APP_KEY=$(grep '^APP_KEY=' .env | cut -d'=' -f2-)
fi

cat > .env << ENVEOF
APP_NAME="${APP_NAME:-Smart Online ISP}"
APP_ENV=${APP_ENV:-local}
APP_KEY=${APP_KEY:-${EXISTING_APP_KEY:-}}
APP_DEBUG=${APP_DEBUG:-true}
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
LOG_LEVEL=${LOG_LEVEL:-debug}

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

if ! grep -q "APP_KEY=base64:" .env; then
  php artisan key:generate --force
fi
echo "  ✓ .env generated with APP_KEY"

# ── Step 6: Clear all caches ─────────────────────────────────────────────────
echo ""
echo "[6/8] Clearing caches..."
php artisan config:clear 2>&1
php artisan cache:clear 2>&1
php artisan route:clear 2>&1
php artisan view:clear 2>&1
php artisan storage:link 2>/dev/null || true
echo "  ✓ Caches cleared, storage linked"

# ── Step 7: Restore database ─────────────────────────────────────────────────
echo ""
echo "[7/8] Setting up database..."

TABLE_COUNT=$(psql -h "${PGHOST}" -p "${PGPORT}" -U "${PGUSER}" -d "${PGDATABASE}" \
  -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE';" 2>/dev/null | tr -d '[:space:]')

if [ -z "$TABLE_COUNT" ] || [ "$TABLE_COUNT" -lt 5 ]; then
  echo "  Fresh database detected..."
  if [ -f "database/full_backup.sql" ]; then
    echo "  Restoring from full backup (this may take a minute)..."
    psql -h "${PGHOST}" -p "${PGPORT}" -U "${PGUSER}" -d "${PGDATABASE}" \
      -f database/full_backup.sql 2>&1 | tail -5
    echo "  ✓ Database restored from backup"
  else
    echo "  No backup found — running migrations + seed..."
    php artisan migrate --seed --force 2>&1
    echo "  ✓ Database migrated and seeded"
  fi
else
  echo "  Existing database found ($TABLE_COUNT tables) — running pending migrations..."
  php artisan migrate --force 2>&1
fi

# ── Step 8: Post-setup checks ────────────────────────────────────────────────
echo ""
echo "[8/8] Running post-setup checks..."
php artisan app:post-setup 2>&1

echo ""
echo "=============================================="
echo "  ✅ Setup Complete!"
echo "=============================================="
echo ""
echo "  Login credentials:"
echo "    Super Admin: superadmin@isp.com / SuperAdmin@123"
echo "    Admin:       admin@isp.com / Test@123"
echo "    Assistant:   rimon@isp.com / rimon@isp.com"
echo ""
echo "  The app will now start automatically."
echo "=============================================="
echo ""
