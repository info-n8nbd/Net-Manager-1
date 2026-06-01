# ISP Billing System - Installation & Deployment Guide

## System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 8.2 | 8.4 |
| PostgreSQL | 14 | 16 |
| RAM | 1 GB | 4 GB |
| Storage | 5 GB | 20 GB |
| OS | Ubuntu 22.04 / CentOS 9 | Ubuntu 24.04 |

### Required PHP Extensions

```
php-pgsql php-mbstring php-xml php-curl php-zip php-bcmath php-gd php-intl php-soap php-tokenizer php-json
```

---

## Quick Start (Any Server)

### 1. Upload Files

Upload all project files to your server. If using the zip archive:

```bash
unzip isp-billing-system-*.zip -d /var/www/isp-billing
cd /var/www/isp-billing
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your settings:

```env
APP_NAME="Your ISP Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Dhaka

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=isp_billing
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 4. Setup Database

```bash
# Create the database
sudo -u postgres createdb isp_billing

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link
```

### 5. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 6. Start the Server

```bash
# Quick test
php artisan serve --host=0.0.0.0 --port=5000

# Or use the production script
bash start-production.sh
```

### 7. Seed Initial Data

```bash
# For fresh installation (creates all tables + seed data)
php artisan migrate:fresh --seed --force

# For updates (only runs new migrations)
php artisan migrate --force
```

### 8. Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@isp.com | SuperAdmin@123 |
| Admin | admin@isp.com | Test@123 |
| Technician | technician@isp.com | Test@123 |
| Billman | billman@isp.com | Test@123 |
| Client | client@isp.com | Test@123 |
| Reseller | reseller@isp.com | Test@123 |
| Sub-Reseller | subreseller@isp.com | Test@123 |
| Manager | manager@isp.com | Test@123 |
| Card Distributor | card@isp.com | Test@123 |

> **IMPORTANT:** Change all passwords immediately after first login!

---

## Deployment Options

### Option A: VPS / Dedicated Server (Recommended)

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/isp-billing/public;
    index index.php;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/isp-billing/public

    <Directory /var/www/isp-billing/public>
        AllowOverride All
        Require all granted
    </Directory>

    FallbackResource /index.php
</VirtualHost>
```

#### Setup Cron (Required)

```bash
# Add to crontab (crontab -e)
* * * * * cd /var/www/isp-billing && php artisan schedule:run >> /dev/null 2>&1
```

#### Setup Queue Worker (Systemd)

Create `/etc/systemd/system/isp-queue.service`:

```ini
[Unit]
Description=ISP Billing Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/isp-billing
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable isp-queue
sudo systemctl start isp-queue
```

### Option B: Replit Hosting

#### New Replit Setup (Copy/Transfer)

1. Import or upload all project files to a new Replit
2. Add **PostgreSQL** database from Replit's database panel (left sidebar → Tools → Database)
3. Run these commands in Replit Shell:

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed --force
php artisan storage:link
```

4. Set the workflow command to: `npx vite --host 0.0.0.0 --port 5000`
5. Click Run — app will start on port 5000
6. Login with: `admin@isp.com` / `Test@123`

> **Note:** Replit auto-sets `DATABASE_URL`. The `.env.example` already has `DB_CONNECTION=pgsql` configured. If DATABASE_URL is set by Replit, it will be used automatically.

#### Publishing to Production

1. Click the **Publish** button in Replit
2. The system automatically runs `start-production.sh` which handles:
   - Database migrations
   - Configuration caching
   - Queue worker (background jobs)
   - Task scheduler (cron jobs)
   - Production server on port 5000

### Option C: Shared Hosting (cPanel)

1. Upload files to `public_html` or a subdirectory
2. Point document root to the `public` folder
3. Create PostgreSQL database via cPanel
4. Update `.env` with database credentials
5. Run via SSH:
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   ```
6. Set up cron job in cPanel:
   ```
   * * * * * cd /home/username/isp-billing && php artisan schedule:run >> /dev/null 2>&1
   ```

### Option D: Localhost / RDP (Windows)

1. Install prerequisites:
   - [XAMPP](https://www.apachefriends.org/) or [Laragon](https://laragon.org/) (recommended)
   - PHP 8.2+ with required extensions
   - PostgreSQL 14+
   - Composer
   - Node.js 18+

2. For **Laragon** (easiest):
   ```
   - Drop project folder into C:\laragon\www\isp-billing
   - Enable PostgreSQL in Laragon preferences
   - Create database: isp_billing
   - Open terminal, cd to project folder
   ```

3. For **manual setup**:
   ```bash
   cd C:\isp-billing
   copy .env.example .env
   php artisan key:generate
   
   # Configure .env with your PostgreSQL credentials
   php artisan migrate:fresh --seed --force
   php artisan storage:link
   
   # Start server
   php artisan serve --port=8000
   ```

4. For **background tasks** on Windows:
   ```bash
   # Open separate terminal for queue worker
   php artisan queue:work --sleep=3 --tries=3
   
   # Open another terminal for scheduler
   php artisan schedule:work
   ```

5. Access at: `http://localhost:8000`

### Option E: Docker

Create `Dockerfile`:

```dockerfile
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev libgd-dev libxml2-dev \
    && docker-php-ext-install pdo_pgsql pgsql mbstring xml zip bcmath gd intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 5000
CMD ["bash", "start-production.sh"]
```

---

## Scheduled Tasks (Cron Jobs)

The system runs these automated tasks:

| Task | Frequency | Description |
|------|-----------|-------------|
| MikroTik Session Sync | Every minute | Syncs active PPPoE/Hotspot sessions |
| Router Health Check | Every 5 min | Monitors router CPU, memory, uptime |
| OLT/ONU Status Poll | Every 5 min | Checks fiber equipment status |
| Area Outage Detection | Every 5 min | Detects mass disconnections |
| Speed Boost Expiry | Every 5 min | Expires temporary speed upgrades |
| Fiber Cut Detection | Every 5 min | Monitors fiber link status |
| Notification Queue | Every 5 min | Processes SMS/WhatsApp messages |
| Auto Ticket Creation | Every 10 min | Creates tickets for offline clients |
| Cron Health Monitor | Every 15 min | Self-monitoring of scheduler |
| Revenue Leakage Check | Every 30 min | Detects billing inconsistencies |
| Client Quality Scores | Hourly | Updates client satisfaction scores |
| Payment Fraud Detection | Hourly | Monitors suspicious payments |
| Ticket Auto-Escalation | Hourly | Escalates overdue tickets |
| SLA Breach Check | Hourly | Checks support ticket SLA compliance |
| Payment Link Expiry | Hourly | Expires old payment links |
| Auto-Disable Overdue | Daily 00:00 | Suspends clients 15+ days overdue |
| Generate Monthly Bills | Daily 00:05 | Creates new monthly bills |
| Auto-Enable After Pay | Daily 00:10 | Re-enables clients who paid |
| Churn Prediction | Daily 02:00 | ML-based churn risk scoring |
| Billing Integrity | Daily 02:00 | Verifies financial consistency |
| Due Reminders | Daily 08:00 | Sends payment reminder SMS |
| Warranty Expiry | Daily 09:00 | Alerts for expiring equipment |
| Reseller Commissions | Monthly 1st | Calculates reseller commissions |

---

## SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

---

## Post-Installation Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate `APP_KEY` with `php artisan key:generate`
- [ ] Configure database credentials
- [ ] Set `APP_URL` to your actual domain
- [ ] Run database migrations
- [ ] Set up SSL certificate
- [ ] Configure cron job for scheduled tasks
- [ ] Set up queue worker (systemd or supervisor)
- [ ] Configure MikroTik devices in Admin Panel
- [ ] Set up SMS gateway in Admin Settings
- [ ] Configure payment gateways (bKash, Nagad, etc.)
- [ ] Create admin user account
- [ ] Set up backup schedule

---

## Troubleshooting

### Database Connection Error
```bash
php artisan config:clear
php artisan cache:clear
# Check .env DB settings match your PostgreSQL config
```

### Permission Denied
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 500 Error
```bash
# Check logs
tail -f storage/logs/laravel.log
# Clear all caches
php artisan optimize:clear
```

### MikroTik Connection Failed
- Ensure API service is enabled on router: `/ip service set api disabled=no`
- Check firewall allows API port (8728): `/ip firewall filter add chain=input protocol=tcp dst-port=8728 action=accept`
- Verify API user has `full` group access

---

## Support

For technical issues, check `storage/logs/laravel.log` for detailed error messages.
