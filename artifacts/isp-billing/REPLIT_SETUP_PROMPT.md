# Smart Online ISP — Complete Replit Setup Guide (A-Z)

> **Purpose:** Upload this file + project files to a NEW Replit account. Tell the AI agent "setup" and everything works with zero issues.
> **Last updated:** March 16, 2026 — 196 migrations, all known bugs permanently fixed.
> **One-line setup:** "Read REPLIT_SETUP_PROMPT.md and set up this project on this new Replit account so it runs perfectly with zero issues."

---

## AI Agent: Complete Setup Checklist

Run ALL of these in order using the code_execution sandbox and bash tool:

### 1. Install System Dependencies

```javascript
// In code_execution sandbox:
await installSystemDependencies({ packages: ["unzip", "net-snmp"] });
await installProgrammingLanguage({ language: "php-8.4" });
```

### 2. Create PostgreSQL Database

```javascript
// In code_execution sandbox:
await createDatabase();
// This auto-sets DATABASE_URL, PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD
```

### 3. Set Environment Variables

```javascript
// In code_execution sandbox:
await setEnvVars({
    values: {
        APP_KEY: "base64:yPbsjh/nM1PLO96MS9ji6dA3Jjc7QhHjpjcYLJM0Mv8=",
        APP_NAME: "Smart Online ISP",
        APP_TIMEZONE: "Asia/Dhaka",
        APP_ENV: "local",
        APP_DEBUG: "true",
        SESSION_DRIVER: "file",
        CACHE_STORE: "file",
        LOG_LEVEL: "debug",
        PHP_CLI_SERVER_WORKERS: "4"
    },
    environment: "shared"
});
```

### 4. Install pnpm Dependencies

```bash
# In bash tool:
cd /home/runner/workspace && pnpm install
```

### 5. Configure and Start the "Start application" Workflow

```javascript
// In code_execution sandbox:
await configureWorkflow({
    name: "Start application",
    command: "bash isp-app/start.sh",
    waitForPort: 5000,
    outputType: "webview",
    autoStart: true
});
```

### 6. Start the Vite Proxy Workflow

After the "Start application" workflow runs, Replit will auto-detect and register the `artifacts/isp-web` artifact. Start its workflow:

```javascript
// In code_execution sandbox:
await restartWorkflow({ workflowName: "artifacts/isp-web: web", timeout: 60 });
```

If that workflow fails, check its logs. The common fix is to verify the package name:
- Directory: `artifacts/isp-web/`
- Package name in `artifacts/isp-web/package.json`: **must be `@workspace/isp-proxy`** (intentional — matches the artifact.toml)
- **NEVER change this package name to `@workspace/isp-web`** — it will break the artifact workflow

---

## Critical: isp-proxy Artifact Naming (PERMANENT FIX — DO NOT UNDO)

> **Why this matters:** The Vite proxy lives in `artifacts/isp-web/` but its `package.json` name is `@workspace/isp-proxy`. This is intentional and must NOT be changed.

The artifact config at `artifacts/isp-web/.replit-artifact/artifact.toml` has:
```toml
[services.development]
run = "pnpm --filter @workspace/isp-proxy run dev"
```

And `isp-app/start.sh` also uses `@workspace/isp-proxy`:
```bash
pnpm --filter @workspace/isp-proxy run dev > /tmp/isp-proxy.log 2>&1 &
```

**Rule: The `package.json` name in `artifacts/isp-web/` must always be `@workspace/isp-proxy`.**

If you see `artifacts/isp-web: web` workflow failing with "No projects matched the filters", it means the package.json name was accidentally changed. Fix it:
```bash
# In bash tool:
grep '"name"' /home/runner/workspace/artifacts/isp-web/package.json
# Should show: "name": "@workspace/isp-proxy"
# If it shows isp-web, change it back to isp-proxy and run: pnpm install
```

---

## Step 1: Create New Repl

1. Go to replit.com → Create Repl → Upload ZIP or Import from GitHub
2. The monorepo uses Node.js 24 + PHP 8.4

---

## Step 2: Environment Configuration

The environment is configured by the AI agent using the checklist above. Key points:

- **APP_KEY** is pre-set: `base64:yPbsjh/nM1PLO96MS9ji6dA3Jjc7QhHjpjcYLJM0Mv8=`
- **DATABASE_URL** is auto-set when the PostgreSQL database is created
- `start.sh` auto-generates `.env` on every startup from these env vars
- No manual `.env` editing needed

**Preview routing at "/" — the isp-web artifact:**
- The `artifacts/isp-web/` directory contains the Vite proxy server (package `@workspace/isp-proxy`)
- It proxies ALL requests to the PHP server on port 5000
- Replit auto-registers this artifact from `artifacts/isp-web/.replit-artifact/artifact.toml`
- Its workflow is named `artifacts/isp-web: web` and runs on port 18558
- **If you see 502 error on the preview:** Start the `artifacts/isp-web: web` workflow

---

## Step 3: Add PostgreSQL Database

Already handled by `createDatabase()` in the AI agent checklist above.
- Auto-sets `DATABASE_URL`, `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`
- `start.sh` auto-parses all these values into the Laravel `.env`

---

## Step 4: Run

1. The "Start application" workflow runs `bash isp-app/start.sh`
2. `start.sh` handles everything automatically:
   - Generates `.env` from environment variables (auto-detects Replit domain for APP_URL)
   - Creates APP_KEY if missing
   - Creates storage directories + symlink
   - Clears all caches
   - Detects fresh vs existing DB (checks user count)
   - Fresh DB: runs `php artisan migrate --seed --force` (all 196 migrations + all seeders)
   - Existing DB: runs `php artisan migrate --force` (pending migrations only)
   - Runs `php artisan app:post-setup` — **this is the permanent safety net** (see below)
   - Starts Laravel scheduler in background
   - Starts PHP server on port 5000

3. **If login fails after first run** (e.g. "credentials do not match"), run manually:
   ```bash
   cd isp-app && php artisan app:post-setup
   ```
   PostSetup.php creates ALL missing users automatically (superadmin, admin, all test users). You should NOT need to manually run `db:seed`.

---

## Step 5: First Login

- **Super Admin:** superadmin@isp.com / SuperAdmin@123
- **Admin (Company 1):** admin@isp.com / Test@123
- **Technician:** technician@isp.com / Test@123
- **Reseller:** reseller@isp.com / Test@123 (reseller_id=1)
- **Billman:** billman@isp.com / Test@123
- **Manager:** manager@isp.com / Test@123
- **Card Distributor:** card@isp.com / Test@123

> **Test MikroTik:** id=2, host=103.85.182.246:8090, user=smartbilling, pw=smart.billing@2026

---

## Architecture Overview

### Tech Stack
- **Backend:** Laravel 12, PHP 8.4, PostgreSQL
- **Frontend:** AdminLTE 3, Bootstrap 4, jQuery 3.6, Font Awesome 6 (CDN), Flatpickr, DataTables, Select2, SweetAlert2, Chart.js
- **Server:** PHP artisan serve on port 5000 (dev & production via isp-web Vite proxy)
- **Auth:** Laravel built-in + spatie/laravel-permission RBAC (13 roles, 112+ permissions + 25 extra from PostSetup)
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** maatwebsite/excel
- **SNMP:** net-snmp CLI tools (snmpget, snmpwalk) — no PHP extension needed
- **Payment Gateways:** SSLCommerz, bKash (Tokenized Checkout v1.2.0-beta), Nagad (RSA-encrypted), Rocket (via SSLCommerz + Webhook)

### 8 User Panels (ALL in English — permanent)
1. Super Admin (company_id = NULL, bypasses TenantAware global scope)
2. Admin
3. Technician
4. Billman
5. Client
6. Reseller
7. Manager
8. Card Distributor

### Multi-Company SaaS Architecture
- **Companies table:** Each ISP company registered in `companies` table. Company 1 = Default ISP.
- **TenantAware global scope:** All major models auto-filter by `auth()->user()->company_id`
- **Super Admin:** `company_id = NULL` — bypasses all TenantAware scopes, manages ALL companies
- **Company isolation:** All data (clients, bills, payments, employees, zones, packages, etc.) is strictly isolated per company
- **Company admin creation:** When Super Admin creates a new company, admin user is auto-created with `user_type='admin'` and the company's `company_id`, then `seedCompanyDefaults()` is called immediately
- **`Company::hasMenuAccess(key)`:** Checks allowed_menus for exact key AND for child-qualified keys
- **Per-company branding:** Sidebar company name uses `Company.name` directly; logo/tagline fall back to Company 1 SystemSettings if company-specific settings not found
- **New company default data:** On company creation, auto-seeds 5 payment methods, 4 accounts, 3 income heads, 4 expense heads, 4 ticket categories, SMS templates (copied from Company 1), 4 departments, 4 designations. PostSetup backfills any existing companies missing this data.

### Database
- **196 migrations** — all finalized, NEVER modify existing ones
- **Seeders** — 4 run in chain: RoleSeeder → SuperAdminSeeder → PermissionSeeder → TestUsersSeeder
- **PostSetup.php** — seeds data (districts, departments, permissions, company defaults) and fixes data issues. ALL schema is now permanently in migrations. PostSetup is data-only + safety net.

---

## Files You Must NEVER Modify

- `bootstrap/app.php` — CSRF exemptions for payment callback routes
- `vite.config.js` — Auto-starts PHP server, queue worker, scheduler for local dev
- `start-production.sh` — Production startup with queue + scheduler
- `database/migrations/*` — All 196 migrations are finalized
- `database/seeders/*` — All seeders are finalized
- `build.sh` — Production build script
- `artifacts/isp-web/.replit-artifact/artifact.toml` — System-managed artifact config (do not edit)

---

## PostSetup.php — The Safety Net (CRITICAL)

`app/Console/Commands/PostSetup.php` runs on EVERY startup and is idempotent.

### Data Seeded / Fixed by PostSetup (every startup):
1. **Bangladesh districts:** 8 divisions + 65 districts in `districts` table (if empty)
2. **Departments + Designations:** 7 ISP departments + 25 designations — only if missing
3. **Permissions:** 25 permissions (store.4, sales.4, inventory.4, reseller.9, technician.4) — firstOrCreate
4. **Super Admin:** If superadmin@isp.com does not exist → creates it (SuperAdmin@123, user_type=super_admin, Super Admin role)
5. **Company 1 admin user:** Ensures admin@isp.com exists with `user_type='admin'`, `company_id=1`, 'admin' role
6. **Orphaned users fix:** Any non-super-admin user with `company_id=NULL` → assigned `company_id=1`
7. **Test users safety net (`ensureTestUsers`):** Creates any missing test users — technician, billman, reseller, manager, card — all with password Test@123 and correct role
8. **Orphaned employees fix:** Any employee with `company_id=NULL` → assigned `company_id=1`
9. **Company default data backfill:** For every company, seeds missing payment methods, accounts, income/expense heads, ticket categories, SMS templates, departments, designations. Idempotent.
10. **Free client cleanup:** Clears due_amount=0 for is_free=true clients, deletes orphaned unpaid bills for free clients
11. **Menu permission key migrations:** client_recovery → client.client_restore; dropdown_options → configuration
12. **Fiber history backfill:** Adds "Assigned By" text to fiber_out records missing it
13. **Client portal setting:** Enables client_portal_change_package if not already set
14. **payment_method_perms table:** Safety net creation if migration somehow missed (idempotent)

---

## Key Fixes Applied (All Permanent in Code/Migrations)

### Multi-Company System
- **`Company::hasMenuAccess($key)`** — checks exact key AND qualified child keys
- **`AppServiceProvider::shareLogoSettings()`** — per-company branding
- **Employee ID per-company isolation** — migration `2026_03_12_000001` changed `employees.employee_id` unique constraint to `(company_id, employee_id)`
- **Company admin credentials management** — Super Admin can edit company admin's name/email/password
- **PostSetup company fixes** — ensures admin@isp.com exists for Company 1, fixes orphaned users/employees
- **New company default seeding** — `CompanyManagementController::store()` calls `seedCompanyDefaults($companyId)` after creating company
- **Per-company unique codes** — `payment_methods.code` and `sms_templates.code` unique per-company

### Billing System (Critical Fixes)
- **`BillingCalculatorService` — ALL billing queries use `withoutGlobalScopes()`** — `calculate()`, `calculateWithBills()`, `getDisplayData()` advance payments all bypass TenantAware scope. Without this, bills and payments with NULL company_id become invisible to non-super-admin.
- **NULL company_id fix** — migration `2026_03_13_231823_fix_null_company_id_on_bills_and_payments` backfills NULL company_id on existing bills/payments rows
- **Auto Bill Generation** — `routes/console.php` uses `Schedule::call()` for critical real-time tasks (SyncMikrotikSessionsJob, ProcessClientSchedulesJob, AutoDisableOverdueClientsJob, AutoEnableAfterPaymentJob, AutoExpireClientsJob, GenerateDailyBillsJob — all every minute; MikrotikSyncJob daily 03:00)
- **Bill auto-update on package change** — `BillingService::updateCurrentBillForPackageChange()` handles overpayment → advance conversion
- **Free client billing** — `BillingCalculatorService::calculate()` forces due=0 when `is_free=true`

### Payment Reason Field (Added March 13–14, 2026)
- **`payments.reason`** column (nullable string) — migration `2026_03_13_235340_add_reason_to_payments_table`
- **All 11 payment forms** across all panels show a **Reason field** that appears automatically when the payment amount differs from the due amount by >0.01, and becomes **required** when visible
- **Field name in forms:** always `name="notes_reason"` (text input, NOT textarea)
- **Controllers save reason** — all pass `'reason' => $request->filled('notes_reason') ? $request->notes_reason : null` to `BillingService::recordPayment()`

### Zone Management Fixes
- **Zone deletion cleanup** — `SettingsController::cleanupZoneReferences()` nulls `zone_id` in ALL FK tables
- **Dual-panel zone picker** — All 4 HRM forms use Available/Assigned dual-panel with arrow buttons and double-click
- **Sentinel hidden input:** `<input type="hidden" name="zone_ids" value="">` before `<select name="zone_ids[]" multiple style="display:none;">` — without this, empty zone lists are never submitted

### Payment Method Permission System
- **Table:** `payment_method_perms` (migration `2026_03_12_140242`)
- **Logic in `User::allowedPaymentMethods()`:** Admin → all methods; user-specific → those methods; role-specific → those methods; no rows → all methods. Also auto-filters by `company_id`.
- **ALL payment forms** use `auth()->user()->allowedPaymentMethods()` — NEVER `PaymentMethod::where('is_active')` directly
- **Admin HRM exception:** `HrmController` uses `PaymentMethod::where('is_active', true)` intentionally — this is the Admin Settings page

### Dashboard & UI
- **41 dashboard widget cards** wrapped with `@if($__canAction('dashboard', 'key'))` guards
- **Dashboard Zero-Cache** — ALL `Cache::remember()` removed from DashboardController
- **Scheduler JS:** `var searchTimeout;` declared at top; `enablePPPoEUser()` method name must match; all functions in global scope

### Reseller Features
- **MAC Reseller Billing System** — per-client day-wise recharge tracking with monthly bill generation
- **Reseller SMS** — tables `reseller_sms_logs`, `reseller_sms_templates` (migrations `2026_03_13_180528` and `2026_03_13_184200`)
- **`allowed_features`** column on resellers — `NULL` means ALL features enabled for reseller
- **PPP Short Code** — per-reseller PPPoE username prefix auto-prepended on client creation
- **Reseller data company_id fix** — migration `2026_03_13_200000` backfills NULL company_id on reseller-related tables

### Reseller Client Edit — P/R.D (Package/Rate/Days) Fix
- **JS toggle `setPrdToggle()`** shows/hides package change fields based on P/R.D checkbox state
- **Disabled fields do NOT submit** — fields are `disabled` (not just hidden) when checkbox is unchecked, preventing accidental package changes
- **Controller gates package update** — `updateClient()` only runs package-change logic when `prd_change === 'true'` in request
- **Duplicate `client_type` field removed** from Personal section (was appearing twice)
- **"Expire Date" label renamed to "Remaining Days"** in service information section

### Reseller Recharge Correction Feature
- **`correctRecharge()`** method in both `ResellerModulesController` (reseller panel) and `ResellerController` (admin panel)
- **Logic:** Creates a `recharge_reversal` record (negative amount, credits prepaid wallet back), then immediately applies a new fresh recharge with corrected package/days/start_date
- **Routes:**
  - Reseller: `reseller.correct-recharge` (POST `/reseller/correct-recharge`) — gated by `recharge_correction` feature
  - Admin: `admin.reseller.correct-recharge` (POST `/admin/reseller/{reseller}/correct-recharge`)
- **Correct button + modal** in both reseller and admin `client-transactions` views
- **`recharge_correction` key** added to `Reseller::$availableFeatures` — NOT in `defaultFeatures()` (admin must explicitly enable per reseller)
- **Reseller sidebar** checks `hasFeature('recharge_correction')` before showing "Credited & Recharge Transactions" menu item
- **Routes grouped** under `recharge_correction` feature middleware in `web.php`
- **`allowed_features=null`** means ALL features enabled (backward compat) — null check before JSON decode

### Reseller Refund / Deactivate Split (March 16, 2026)
- **Two separate actions** — previously one "Refund" button did both financial refund AND deactivation. Now split into two distinct operations:

#### Refund (Financial Only)
- **`refundRecharge()`** in `ResellerController` — financial-only:
  1. Creates `recharge_refund` record (negative amount)
  2. Credits reseller prepaid wallet (if billing_type = 'prepaid')
  3. **Subtracts refunded days from `client->expire_date`** — client stays active but expiry reduces by `$recharge->days`
  4. Logs activity
- **Client status:** STAYS active
- **Reseller Billing (funding page):** `summary_total_billed = ResellerBill total − recharge_refund total` — Due automatically decreases after refund
- **Route:** `admin.reseller.client.refund-recharge` (POST)
- **Button color:** Blue (info)

#### Deactivate (No Financial Change)
- **`deactivateResellerClient()`** in `ResellerController` — deactivation only:
  1. Sets `client->status = 'inactive'`
  2. Sets `client->expire_date = yesterday`
  3. Disables client on MikroTik (PPPoE/Hotspot/Static)
  4. No financial change whatsoever
- **Route:** `admin.reseller.client.deactivate` (POST `/admin/reseller/{reseller}/clients/{client}/deactivate`)
- **Button color:** Red (danger)

#### Admin Client Transactions View
- **3 separate buttons per recharge row:** Orange (Correct), Blue (Refund), Red (Deactivate)
- **Double-refund prevention:** Controller passes `$refundedIds` (keyed array of already-refunded recharge IDs) to view. Refunded recharge rows show "Refunded" badge instead of action buttons. Same logic in both admin and reseller controllers.

### Agent / Referral Partner Module
- **Tables:** `agents`, `agent_commissions`
- Route permissions: `admin.agent.clients` and `admin.agent.resellers` both mapped to `agent.list`

### Collection Transfers
- Supports transfers to both accounts AND staff users
- `to_account_id` is nullable; `to_user_id` added for staff transfers

### Installation Charge System
- Base entry pattern: `paid_by IS NULL` = charge record, `paid_by IS NOT NULL` = payment record
- Status calculated dynamically (Free/Paid/Partial/Due)

---

## Deployment (Production on Replit)

### Deploy Steps
1. Go to Deploy tab → Deploy
2. Uses `build.sh` (build step) + `start-production.sh` (run step via `dist/index.cjs` bridge)
3. `build.sh` creates `dist/index.cjs` which launches `start-production.sh`
4. Production uses: `APP_ENV=production`, `APP_DEBUG=false`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`

### Production Startup Order (`start-production.sh`)
1. Start PHP server immediately (port 5000) — so health check passes
2. Sleep 2 seconds
3. Run migrations + post-setup
4. Cache config/routes/views + reset permission cache
5. Start queue worker (background)
6. Start scheduler (background)
7. Wait for server process

### Fresh Install Command
```bash
cd isp-app && php artisan migrate:fresh --seed --force && php artisan app:post-setup
```

---

## Environment Variables

### Required (set via AI agent checklist above):
- `DATABASE_URL` — auto-set by Replit PostgreSQL addon
- `APP_KEY` — `base64:yPbsjh/nM1PLO96MS9ji6dA3Jjc7QhHjpjcYLJM0Mv8=`
- `APP_NAME` — "Smart Online ISP"
- `APP_TIMEZONE` — "Asia/Dhaka"
- `APP_ENV` — "local"
- `APP_DEBUG` — "true"
- `SESSION_DRIVER` — "file"
- `CACHE_STORE` — "file"
- `LOG_LEVEL` — "debug"
- `PHP_CLI_SERVER_WORKERS` — "4"

### Optional (configure in Admin Panel instead):
- Payment gateways (bKash, Nagad, SSLCommerz) — configure via Admin > Settings > Payment
- SMS settings — configure via Admin > Settings > SMS
- MikroTik devices — configure via Admin > Network > Devices

---

## Troubleshooting

### "Table not found" errors
Run: `cd isp-app && php artisan app:post-setup` — also run `php artisan migrate --force`

### Stale cache
Run: `cd isp-app && php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear`

### Login fails for Admin (company user)
- Default: admin@isp.com / Test@123
- PostSetup auto-creates admin@isp.com if missing
- Check `users` table `company_id` = 1 and `user_type` = 'admin'

### Login fails for Super Admin
- Default: superadmin@isp.com / SuperAdmin@123

### Company user gets 403 on Dashboard
- `Company::hasMenuAccess()` checks child keys — already fixed
- Check `companies.allowed_menus` includes dashboard keys

### New company has no payment methods / accounts
- `CompanyManagementController::store()` calls `seedCompanyDefaults($companyId)` automatically
- Run: `cd isp-app && php artisan app:post-setup`

### Bills/payments invisible to non-super-admin
- All `BillingCalculatorService` queries use `withoutGlobalScopes()` — already fixed
- Run migration: `php artisan migrate --force`

### Migration errors on fresh DB
Run: `cd isp-app && php artisan migrate:fresh --seed --force && php artisan app:post-setup`

### SNMP not working
- `net-snmp` must be installed as a system dependency
- OLT must whitelist server IP for SNMP (port 161 UDP)

### Port 5000 not responding
- Check "Start application" workflow is running `bash isp-app/start.sh`

### Preview shows 502 error
- The `artifacts/isp-web: web` workflow is not running
- Start it: in code_execution sandbox → `await restartWorkflow({ workflowName: "artifacts/isp-web: web" })`
- If it fails with "No projects matched the filters": the package.json name in `artifacts/isp-web/` was changed — revert it to `@workspace/isp-proxy` and run `pnpm install`

### "artifacts/isp-web: web" workflow fails immediately
- Check `artifacts/isp-web/package.json` — `"name"` must be `"@workspace/isp-proxy"`
- Run `pnpm install` after fixing, then restart the workflow

### Queue jobs not processing
- Dev mode: Critical jobs use `Schedule::call()` — run inline, no queue needed
- Production: `start-production.sh` starts queue worker automatically

### "Class not found" or autoload errors
Run: `cd isp-app && composer dump-autoload`

### Permission errors (storage/bootstrap)
Run: `chmod -R 775 isp-app/storage isp-app/bootstrap/cache`

### Employee IDs duplicating across companies
- Migration `2026_03_12_000001` changed unique constraint to `(company_id, employee_id)` — correct behavior

### Zone deletion causes 500 error
- `SettingsController::cleanupZoneReferences()` handles all FK cleanup — already fixed

### Payment reason field not showing
- Requires amount to differ from due amount by more than 0.01
- Field auto-appears and becomes required — implemented in all 11 payment forms

### Reseller payment method not respecting role permissions
- `ResellerModulesController::collectPaymentForm` and `ResellerDashboardController::index` use `auth()->user()->allowedPaymentMethods()` — already fixed

### Reseller Refund not reducing Due on Billing page
- `funding()` method computes `totalBilled = bills - refunds` — already fixed
- Due automatically decreases after refund

### Refund not reducing client's Remaining Days
- `refundRecharge()` subtracts `$recharge->days` from `client->expire_date` — already fixed
- Client stays active; only expiry date is adjusted

### Reseller Recharge Correction button not showing
- Reseller must have `recharge_correction` feature enabled by admin (Feature Control page)
- `allowed_features=null` means ALL features enabled — reseller with null allowed_features always sees it

### Double refund possible on same recharge
- Both admin and reseller controllers compute `$refundedIds` — already-refunded recharge rows show "Refunded" badge; action buttons are hidden

---

## Important Technical Notes (For AI Agent)

### Permanent Rules — NEVER break these:
- **Language:** ALL panels use English permanently. NEVER output Bengali/Bangla text in code, views, error messages, or comments.
- **Database:** ALWAYS use `php artisan make:migration` for ANY schema change. NEVER use tinker/raw SQL to add columns or tables.
- **TenantAware scope:** Bills and Payments with NULL company_id are invisible to non-super-admin. ALWAYS use `withoutGlobalScopes()` in BillingCalculatorService billing queries.
- **Migration count:** Currently 196. Every new migration increments this. NEVER modify existing migrations.
- **Zone picker sentinel:** ALL dual-panel zone pickers MUST have `<input type="hidden" name="zone_ids" value="">` BEFORE the `<select name="zone_ids[]" multiple>`. Without this, empty zone lists are never submitted.
- **Payment reason field:** ALL payment forms across ALL panels (Admin, Technician, Reseller, Billman) must show a required "Reason" field when amount ≠ due by >0.01. Field name is always `name="notes_reason"`. Controllers MUST pass `'reason' => $request->filled('notes_reason') ? $request->notes_reason : null` to `BillingService::recordPayment()`. Currently 11 forms total.
- **Payment method permission rule:** ALL payment form views MUST use `auth()->user()->allowedPaymentMethods()`. The ONLY exceptions are Admin HRM/Settings pages that show ALL methods for configuration purposes.
- **P/R.D checkbox rule:** In reseller client edit, the P/R.D fields MUST use `disabled` attribute (not just hidden) when unchecked. Controller MUST check `$request->prd_change === 'true'` before applying package changes.
- **Recharge Correction `recharge_correction` feature:** NOT in `defaultFeatures()`. Admin must explicitly enable per reseller. `allowed_features=null` means ALL features enabled — always null-check before JSON decode.
- **Refund vs Deactivate:** These are TWO separate operations. Refund = financial only + adjusts expire_date (client stays active). Deactivate = status change + MikroTik disable (no financial change). NEVER combine them back into one action.
- **Reseller Billing Due:** Always compute `totalBilled = ResellerBill total - recharge_refund total` in the `funding()` method. NEVER use raw bill total without subtracting refunds.
- **Double-refund prevention:** Both admin and reseller `clientTransactions()` controllers MUST compute `$refundedIds` (keyed array) and pass to view. Blade condition: `@if($isRecharge && $txn->client && !isset($refundedIds[$txn->id]))`.
- **isp-proxy package name:** `artifacts/isp-web/package.json` name MUST always be `"@workspace/isp-proxy"`. NEVER change it to `@workspace/isp-web`.

### Architecture Rules:
- **TenantContext::get()** is ONLY set in seeders/jobs — always use `auth()->user()->company_id` in controllers
- **jQuery loads AFTER @yield('content')** in admin layout — all jQuery scripts MUST go in `@push('scripts')`, NOT inside `@section('content')`
- **Database is PostgreSQL only** — many queries use PostgreSQL-specific features
- **`pppoe_username` column does NOT exist** in clients table — always use `username` field
- **`connection_status`** is the source of truth for online/offline (not `session_uptime`)
- **Client search widget:** Always use `@include('admin.partials.client-search', [...])` — NEVER use Select2
- **`instruments` table:** Uses `category` and `sub_category` columns (not `category_id`/`sub_category_id`)
- **Sidebar is hardcoded HTML** in `resources/views/layouts/admin.blade.php`
- **Name splitting:** No DB columns for first_name/last_name; backend merges in controllers
- **Installation Charge base entries:** `paid_by IS NULL` = charge record, `paid_by IS NOT NULL` = payment record
- **Application status "submitted"** displays as "Pending" via model accessor — DB value remains `submitted`
- **Blade @json() rule:** Never use PHP closures inside @json() — use @php block to prepare data first
- **Sale routes order:** Static routes before dynamic `{sale}` param routes in web.php
- **`clients` table has `client_id`** (not `client_code`)
- **OLT SNMP form:** Username/password hidden for SNMP connections; single Community field
- **Scheduler JS:** `var searchTimeout;` must be declared; `enablePPPoEUser()` method name must match; all functions in global scope
- **start.sh** auto-generates `.env` from Replit environment variables — no manual .env creation needed
- **`mikrotik_devices` table does NOT have `is_enabled` column** — do not reference it
- **`allowed_features=null` on resellers** means ALL features enabled — null check first, then JSON decode
- **Menu key sync (3-source rule):** Any menu change must update ALL 3: `admin.blade.php`, `config/menu.php`, `Company.php $menuStructure`
- **All payment forms use `auth()->user()->allowedPaymentMethods()`** — NEVER `PaymentMethod::where('is_active')` directly on payment forms
- **`OltDevice` columns:** DB has `vendor`, `total_ports`, `is_active`, `status`
- **`onus` table columns:** `serial_number`, `olt_device_id`, `rx_power`, `tx_power`, `status`, `signal_quality`, `distance`, `down_reason`, `last_online`
- **`ClientActivityService::log()` parameter names:** `client`, `actionType`, `oldValue`, `newValue`, `reason`, `triggeredBy`, `performedBy`, `extraData` — NO `description` parameter
- **Reseller billing summary:** `summary_total_billed = ResellerBill::sum('total_recharge') - ResellerClientRecharge::where('recharge_refund')::sum('recharge_amount')`
- **`$refundedIds` in clientTransactions:** Built by parsing `"Refund for recharge #(\d+)"` regex from `recharge_refund` remarks. Keyed array for O(1) Blade lookup with `isset($refundedIds[$txn->id])`.

### Blade Scripting Rule (CRITICAL):
- **ALWAYS use `@push('scripts') / @endpush`** for JavaScript in views — NEVER `@section('scripts') / @endsection`
- The admin layout stacks scripts after jQuery is loaded — `@section('scripts')` would override the stack entirely

---

## Complete Feature List

### Admin Panel
- Multi-company management (Super Admin)
- Client management (CRUD, package change, status, billing)
- Package management (PPPoE/Static/Hotspot)
- Zone/Area management
- MikroTik device management (PPPoE/Hotspot/Static)
- OLT device management + ONU monitoring (SNMP)
- Employee management (HRM) with zone assignments
- Billing system (monthly bills, payments, due reports)
- Payment method management with per-role permission system
- Reseller management (MAC/Bandwidth types, prepaid/postpaid)
- Reseller billing (funding/payment tracking, Due calculation with refund offset)
- Reseller client transactions (Correct/Refund/Deactivate — 3 separate actions)
- Agent/Referral partner management
- Inventory management
- Store/Sales management
- Reports (billing, collection, due, payment, client, zone, reseller)
- Ticket system
- SMS system (templates, logs)
- New connection applications
- Card distributor system
- Collection transfer (account-to-account, account-to-staff)
- Dashboard with 41 permission-gated widgets

### Reseller Panel
- Client management (create, edit, recharge, status)
- P/R.D (Package/Rate/Days) change with JS-gated form
- Client transactions (Recharge, Curtail, Correction — feature-gated)
- Recharge Correction feature (reversal + re-apply, gated by `recharge_correction` feature)
- Collect payment
- SMS system
- Reports
- Billing (postpaid: due to parent admin)

### Other Panels
- **Technician:** Client list, payment collection, due report, zone-filtered
- **Billman:** Client payment collection
- **Manager:** Reporting and oversight
- **Card Distributor:** Card sales management
- **Client Portal:** Package info, bills, payments, change package request

---

*Last updated: March 16, 2026 — 196 migrations. All panels English. All features production-ready.*
