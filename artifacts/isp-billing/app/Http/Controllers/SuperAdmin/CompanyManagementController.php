<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CompanyManagementController extends Controller
{
    protected function ensureSuperAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this area.');
        }
    }

    public function index()
    {
        $this->ensureSuperAdmin();

        $companies = Company::withCount(['users', 'clients'])->orderBy('created_at', 'desc')->get();

        // Eager-load admin users to avoid N+1 queries in the index view
        $companyIds = $companies->pluck('id')->all();
        $adminUsersByCompany = User::whereIn('company_id', $companyIds)
            ->where('user_type', 'admin')
            ->get()
            ->keyBy('company_id');

        return view('superadmin.companies.index', compact('companies', 'adminUsersByCompany'));
    }

    public function create()
    {
        $this->ensureSuperAdmin();

        $allFeatures = Company::$allFeatures;
        $menuStructure = Company::$menuStructure;

        return view('superadmin.companies.create', compact('allFeatures', 'menuStructure'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
            'max_clients' => 'nullable|integer|min:0',
            'subscription_start' => 'nullable|date',
            'subscription_end' => 'nullable|date',
            'enabled_features' => 'nullable|array',
            'allowed_menus' => 'nullable|array',
            'page_features' => 'nullable|array',
        ]);

        $enabledFeatures = $request->enabled_features ?? array_keys(Company::$allFeatures);
        $allowedMenus = $request->allowed_menus ?? [];

        // Unique slug — append suffix if already taken
        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $slugSuffix = 2;
        while (Company::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $slugSuffix++;
        }

        $company = Company::create([
            'name' => $request->name,
            'slug' => $slug,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'max_clients' => $request->max_clients ?? 0,
            'enabled_features' => $enabledFeatures,
            'allowed_menus' => $allowedMenus,
            'is_active' => true,
            'subscription_start' => $request->subscription_start ?? now(),
            'subscription_end' => $request->subscription_end,
        ]);

        $adminUser = User::create([
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'user_type' => 'admin',
            'company_id' => $company->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $adminUser->assignRole($adminRole);

        $this->seedCompanyDefaults($company->id);

        \App\Services\MenuPermissionService::clearCompanyUsersCache($company->id);

        return redirect()->route('superadmin.companies.index')
            ->with('success', 'Company "' . $company->name . '" created successfully with admin account.');
    }

    public function show(Company $company)
    {
        $this->ensureSuperAdmin();

        $company->loadCount(['users', 'clients']);
        $admins = User::where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('user_type', 'admin')
                  ->orWhereHas('roles', function ($r) {
                      $r->where('name', 'Administrator');
                  });
            })->get();

        $allFeatures = Company::$allFeatures;
        $menuStructure = Company::$menuStructure;

        return view('superadmin.companies.show', compact('company', 'admins', 'allFeatures', 'menuStructure'));
    }

    public function edit(Company $company)
    {
        $this->ensureSuperAdmin();

        $allFeatures = Company::$allFeatures;
        $menuStructure = Company::$menuStructure;
        $adminUser = User::where('company_id', $company->id)->where('user_type', 'admin')->first();

                $pageFeatures = \App\Models\Company::$pageFeatures;
        return view('superadmin.companies.edit', compact('company', 'allFeatures', 'menuStructure', 'adminUser', 'pageFeatures'));
    }

    public function update(Request $request, Company $company)
    {
        $this->ensureSuperAdmin();

        $adminUser = User::where('company_id', $company->id)->where('user_type', 'admin')->first();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'max_clients' => 'nullable|integer|min:0',
            'subscription_start' => 'nullable|date',
            'subscription_end' => 'nullable|date',
            'enabled_features' => 'nullable|array',
            'allowed_menus' => 'nullable|array',
            'page_features' => 'nullable|array',
        ];

        if (!$adminUser) {
            // No existing admin — creating a new one, all three fields required together
            if ($request->filled('admin_email') || $request->filled('admin_name') || $request->filled('admin_password')) {
                $rules['admin_name']     = 'required|string|max:255';
                $rules['admin_email']    = 'required|email|max:255|unique:users,email';
                $rules['admin_password'] = 'required|string|min:8';
            }
        } else {
            if ($request->filled('admin_email')) {
                $rules['admin_email'] = 'required|email|max:255|unique:users,email,' . $adminUser->id;
            }
            if ($request->filled('admin_password')) {
                $rules['admin_password'] = 'required|string|min:8';
            }
        }

        $request->validate($rules);

        $newSlug = Str::slug($request->name);
        if ($newSlug !== $company->slug) {
            $baseSlug = $newSlug;
            $slugSuffix = 2;
            while (Company::where('slug', $newSlug)->where('id', '!=', $company->id)->exists()) {
                $newSlug = $baseSlug . '-' . $slugSuffix++;
            }
        }

        $company->update([
            'name' => $request->name,
            'slug' => $newSlug,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'max_clients' => $request->max_clients ?? 0,
            'enabled_features' => $request->enabled_features ?? [],
            'allowed_menus' => $request->allowed_menus ?? [],
            'page_features' => $request->page_features ?? [],
            'subscription_start' => $request->subscription_start,
            'subscription_end' => $request->subscription_end,
        ]);

        if ($adminUser) {
            $adminData = [];
            if ($request->filled('admin_name')) {
                $adminData['name'] = $request->admin_name;
            }
            if ($request->filled('admin_email')) {
                $adminData['email'] = $request->admin_email;
            }
            if ($request->filled('admin_password')) {
                $adminData['password'] = Hash::make($request->admin_password);
            }
            if (!empty($adminData)) {
                $adminUser->update($adminData);
            }
        } elseif ($request->filled('admin_email') && $request->filled('admin_name') && $request->filled('admin_password')) {
            // Create a brand-new admin user for this company
            $newAdmin = User::create([
                'name'              => $request->admin_name,
                'email'             => $request->admin_email,
                'password'          => Hash::make($request->admin_password),
                'user_type'         => 'admin',
                'company_id'        => $company->id,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);
            $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
            $newAdmin->assignRole($adminRole);
        }

        \App\Services\MenuPermissionService::clearCompanyUsersCache($company->id);

        return redirect()->route('superadmin.companies.index')
            ->with('success', 'Company "' . $company->name . '" updated successfully.');
    }

    public function toggleStatus(Company $company)
    {
        $this->ensureSuperAdmin();

        $company->update(['is_active' => !$company->is_active]);

        $status = $company->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', 'Company "' . $company->name . '" has been ' . $status . '.');
    }

    public function loginAs(Company $company)
    {
        $this->ensureSuperAdmin();

        $admin = User::where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('user_type', 'admin')
                  ->orWhereHas('roles', function ($r) {
                      $r->where('name', 'Administrator');
                  });
            })
            ->first();

        if (!$admin) {
            return redirect()->back()->with('error', 'No admin user found for this company.');
        }

        session(['impersonating_from' => auth()->id()]);

        Auth::login($admin);

        return redirect()->route('admin.dashboard')
            ->with('success', 'You are now viewing as ' . $company->name . ' admin.');
    }

    public function switchBack()
    {
        $originalUserId = session('impersonating_from');

        if (!$originalUserId) {
            return redirect()->route('admin.dashboard');
        }

        $originalUser = User::find($originalUserId);

        if (!$originalUser || !$originalUser->isSuperAdmin()) {
            session()->forget('impersonating_from');
            return redirect()->route('admin.dashboard')->with('error', 'Original user not found.');
        }

        session()->forget('impersonating_from');
        Auth::login($originalUser);

        return redirect()->route('superadmin.companies.index')
            ->with('success', 'Switched back to Super Admin successfully.');
    }


    public function destroy(Company $company)
    {
        $id   = $company->id;
        $name = $company->name;

        try {
            \DB::transaction(function () use ($id) {
                // Child detail tables first (avoid FK conflicts)
                $tables = [
                    'olt_snmp_stats', 'olt_traffic_stats', 'olt_ports',
                    'bill_corrections', 'bill_items', 'reseller_bill_payments',
                    'reseller_client_recharges', 'client_additional_payments',
                    'client_activity_logs', 'client_documents', 'client_line_shifts',
                    'client_reconnections', 'client_recovery_logs', 'client_schedules',
                    'client_status_logs', 'technician_assignments', 'ticket_messages',
                    'purchase_items', 'sale_items', 'salary_payments',
                    'employee_selfie_attendances', 'attendances', 'leaves',
                    'agent_commissions', 'reseller_commissions', 'collection_transfers',
                    'reseller_payments', 'reseller_wallets', 'reseller_bills',
                    'scratch_cards', 'scratch_card_batches', 'card_distributors',
                    'returned_equipments', 'vendor_ledgers', 'staff_submissions',
                    'staff_dues', 'fund_transfers', 'advance_payments',
                    'due_reminders', 'android_sms_logs', 'sms_logs',
                    'whatsapp_templates', 'webhook_payment_logs',
                    'online_client_history', 'network_event_logs',
                    'revenue_leakage_alerts', 'audit_logs', 'activity_logs',
                    'login_logs', 'cron_job_logs',
                    // Main transactional tables
                    'payments', 'bills', 'collections', 'daily_collections',
                    'daily_cash_closes', 'month_closes',
                    'incomes', 'expenses', 'purchases', 'sales',
                    'salaries', 'tasks', 'events', 'announcements',
                    'tickets', 'ticket_categories',
                    // Client & user data
                    'clients', 'agents', 'resellers', 'employees',
                    'vpn_accounts', 'promo_codes', 'discounts',
                    'new_connection_applications',
                    // Network equipment
                    'onus', 'onu_devices', 'olt_devices', 'mikrotik_devices',
                    'mikrotik_profiles', 'ip_addresses', 'ip_pools',
                    'splitters', 'pops', 'boxes',
                    // Config/settings
                    'packages', 'zones', 'areas', 'branches',
                    'products', 'product_categories',
                    'instruments', 'instrument_categories',
                    'accounts', 'banks', 'expense_heads', 'income_heads',
                    'departments', 'designations',
                    'payment_methods', 'payment_method_perms',
                    'menu_permissions', 'user_menu_permissions',
                    'role_permissions',
                    'sms_settings', 'sms_templates',
                    'whatsapp_settings', 'voice_call_settings',
                    'system_settings', 'automation_settings',
                    'auto_inactive_skip_dates',
                    'knowledge_base_articles', 'knowledge_base_categories',
                    'manager_assignments',
                    'company_profiles',
                    // Users last
                    'users',
                ];

                foreach ($tables as $table) {
                    try {
                        \DB::table($table)->where('company_id', $id)->delete();
                    } catch (\Throwable $e) {
                        \Log::warning("Company delete: could not delete from {$table}: " . $e->getMessage());
                    }
                }
            });

            $company->delete();

            return redirect()->route('superadmin.companies.index')
                ->with('success', "Company \"{$name}\" and all its data deleted successfully.");

        } catch (\Throwable $e) {
            \Log::error("Company delete failed: " . $e->getMessage());
            return back()->with('error', "Failed to delete company: " . $e->getMessage());
        }
    }

    public function seedCompanyDefaults(int $companyId): void
    {
        $now = now();

        // Payment Methods (codes must be uppercase to match app-wide conventions)
        $paymentMethods = [
            ['name' => 'Cash',          'code' => 'CASH',  'description' => 'Cash payment',          'is_active' => true],
            ['name' => 'bKash',         'code' => 'BKASH', 'description' => 'bKash mobile banking',  'is_active' => true],
            ['name' => 'Nagad',         'code' => 'NAGAD', 'description' => 'Nagad mobile banking',  'is_active' => true],
            ['name' => 'Rocket',        'code' => 'ROCKET','description' => 'Rocket mobile banking', 'is_active' => true],
            ['name' => 'Bank Transfer', 'code' => 'BANK',  'description' => 'Bank transfer',         'is_active' => true],
        ];
        foreach ($paymentMethods as $pm) {
            $exists = DB::table('payment_methods')
                ->where('company_id', $companyId)
                ->where('code', $pm['code'])
                ->exists();
            if (!$exists) {
                DB::table('payment_methods')->insert(array_merge($pm, [
                    'company_id' => $companyId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // Accounts
        $accounts = [
            ['name' => 'Main Cash',    'type' => 'cash',          'account_number' => null,          'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true],
            ['name' => 'bKash Account','type' => 'mobile_banking', 'account_number' => '01700000000', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true],
            ['name' => 'Nagad Account','type' => 'mobile_banking', 'account_number' => '01700000001', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true],
            ['name' => 'Bank Account', 'type' => 'bank',           'account_number' => null,          'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true],
        ];
        foreach ($accounts as $acc) {
            $exists = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('name', $acc['name'])
                ->whereNull('deleted_at')
                ->exists();
            if (!$exists) {
                DB::table('accounts')->insert(array_merge($acc, [
                    'company_id' => $companyId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // Income Heads
        $incomeHeads = ['Monthly Subscription', 'New Connection Fee', 'Equipment Sale'];
        foreach ($incomeHeads as $ih) {
            $exists = DB::table('income_heads')->where('company_id', $companyId)->where('name', $ih)->exists();
            if (!$exists) {
                DB::table('income_heads')->insert(['name' => $ih, 'is_active' => true, 'company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // Expense Heads
        $expenseHeads = ['Salary', 'Rent', 'Utilities', 'Equipment Purchase'];
        foreach ($expenseHeads as $eh) {
            $exists = DB::table('expense_heads')->where('company_id', $companyId)->where('name', $eh)->exists();
            if (!$exists) {
                DB::table('expense_heads')->insert(['name' => $eh, 'is_active' => true, 'company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // Ticket Categories
        $ticketCategories = ['Internet Issue', 'Billing Issue', 'New Connection', 'Technical Support'];
        foreach ($ticketCategories as $tc) {
            $exists = DB::table('ticket_categories')->where('company_id', $companyId)->where('name', $tc)->exists();
            if (!$exists) {
                DB::table('ticket_categories')->insert(['name' => $tc, 'is_active' => true, 'company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // SMS Templates — seed built-in defaults (not copied from any specific company)
        $defaultSmsTemplates = [
            [
                'name'    => 'Bill Generated',
                'type'    => 'bill_generated',
                'code'    => 'BILL_GEN',
                'content' => 'Dear {client_name}, your bill of {amount} BDT has been generated for {month}. Due date: {due_date}. - {company_name}',
            ],
            [
                'name'    => 'Payment Received',
                'type'    => 'payment_received',
                'code'    => 'PAY_RECV',
                'content' => 'Dear {client_name}, we received your payment of {amount} BDT on {date}. Thank you! - {company_name}',
            ],
            [
                'name'    => 'Account Activated',
                'type'    => 'account_activated',
                'code'    => 'ACC_ACT',
                'content' => 'Dear {client_name}, your account has been activated. Enjoy our service! - {company_name}',
            ],
            [
                'name'    => 'Account Deactivated',
                'type'    => 'account_deactivated',
                'code'    => 'ACC_DEACT',
                'content' => 'Dear {client_name}, your account has been deactivated due to non-payment. Please pay your dues to restore service. - {company_name}',
            ],
            [
                'name'    => 'Due Reminder',
                'type'    => 'due_reminder',
                'code'    => 'DUE_REM',
                'content' => 'Dear {client_name}, you have a pending due of {amount} BDT. Please pay to avoid service interruption. - {company_name}',
            ],
        ];
        foreach ($defaultSmsTemplates as $tpl) {
            $exists = DB::table('sms_templates')
                ->where('company_id', $companyId)
                ->where('type', $tpl['type'])
                ->exists();
            if (!$exists) {
                DB::table('sms_templates')->insert([
                    'name'       => $tpl['name'],
                    'type'       => $tpl['type'],
                    'content'    => $tpl['content'],
                    'code'       => $tpl['code'],
                    'is_active'  => true,
                    'company_id' => $companyId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Departments + Designations (department-linked for correct dropdown mapping)
        $companyDeptDesignations = [
            'Technical' => [
                'NOC Engineer', 'Network Engineer', 'NOC Technician',
                'Senior Technical Support Engineer', 'Technical Support Engineer', 'Support Technician',
            ],
            'Sales' => [
                'Sales Manager', 'Sales Executive', 'Marketing Executive',
            ],
            'Accounts' => [
                'Accounts Manager', 'Accounts Executive', 'Collection Executive',
            ],
            'Administration' => [
                'Operations Manager', 'HR Manager', 'Administrative Officer',
                'Chief Executive Officer (CEO)', 'Chief Technology Officer (CTO)',
            ],
        ];

        foreach ($companyDeptDesignations as $deptName => $designations) {
            $dept = DB::table('departments')
                ->where('company_id', $companyId)->where('name', $deptName)->first();
            if (!$dept) {
                $deptId = DB::table('departments')->insertGetId([
                    'name' => $deptName, 'is_active' => true,
                    'company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now,
                ]);
            } else {
                $deptId = $dept->id;
            }

            foreach ($designations as $desigName) {
                if (!DB::table('designations')
                    ->where('department_id', $deptId)->where('name', $desigName)->exists()) {
                    DB::table('designations')->insert([
                        'name' => $desigName, 'department_id' => $deptId,
                        'company_id' => $companyId, 'is_active' => true,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }

        // System Settings — essential operational defaults per company
        $defaultSystemSettings = [
            ['key' => 'auto_disable_days',    'value' => '7',    'group' => 'general'],
            ['key' => 'bill_generation_day',  'value' => '1',    'group' => 'general'],
            ['key' => 'due_days',             'value' => '15',   'group' => 'general'],
            ['key' => 'sms_gateway',          'value' => 'none', 'group' => 'general'],
            ['key' => 'currency_symbol',      'value' => '৳',    'group' => 'general'],
            ['key' => 'client_portal_change_package', 'value' => '1', 'group' => 'portal'],
            ['key' => 'client_portal_active', 'value' => '1',    'group' => 'portal'],
        ];
        foreach ($defaultSystemSettings as $setting) {
            $exists = DB::table('system_settings')
                ->where('company_id', $companyId)
                ->where('key', $setting['key'])
                ->exists();
            if (!$exists) {
                DB::table('system_settings')->insert(array_merge($setting, [
                    'company_id' => $companyId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // Automation Settings — billing automation defaults
        $hasAutomation = DB::table('automation_settings')
            ->where('company_id', $companyId)
            ->exists();
        if (!$hasAutomation) {
            DB::table('automation_settings')->insert([
                'bill_generate_time'   => '09:00',
                'disconnect_time'      => '23:59',
                'min_due_for_inactive' => 0,
                'company_id'           => $companyId,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }

        // Default Internet Packages — required for client creation
        $defaultPackages = [
            ['name' => '5 Mbps Basic',   'code' => "C{$companyId}-5M",   'price' => 500,  'bandwidth' => '5/5',   'download_speed' => 5,   'upload_speed' => 5,   'speed_unit' => 'Mbps', 'connection_type' => 'PPPoE'],
            ['name' => '10 Mbps Home',   'code' => "C{$companyId}-10M",  'price' => 800,  'bandwidth' => '10/10', 'download_speed' => 10,  'upload_speed' => 10,  'speed_unit' => 'Mbps', 'connection_type' => 'PPPoE'],
            ['name' => '20 Mbps Office', 'code' => "C{$companyId}-20M",  'price' => 1200, 'bandwidth' => '20/20', 'download_speed' => 20,  'upload_speed' => 20,  'speed_unit' => 'Mbps', 'connection_type' => 'PPPoE'],
            ['name' => '50 Mbps Pro',    'code' => "C{$companyId}-50M",  'price' => 2000, 'bandwidth' => '50/50', 'download_speed' => 50,  'upload_speed' => 50,  'speed_unit' => 'Mbps', 'connection_type' => 'PPPoE'],
        ];
        foreach ($defaultPackages as $pkg) {
            $exists = DB::table('packages')
                ->where('company_id', $companyId)
                ->where('code', $pkg['code'])
                ->whereNull('deleted_at')
                ->exists();
            if (!$exists) {
                DB::table('packages')->insert(array_merge($pkg, [
                    'company_id'    => $companyId,
                    'validity_days' => 30,
                    'is_active'     => true,
                    'show_in_public'=> false,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]));
            }
        }


        // Bandwidth Categories — 6 ISP-standard system categories
        $bandwidthCategories = [
            ['key' => 'intl',  'label' => 'International (Raw)',                 'icon' => 'fas fa-globe',         'color' => 'secondary', 'sort_order' => 1, 'is_system' => true],
            ['key' => 'ggc',   'label' => 'GGC (Google Global Cache)',           'icon' => 'fab fa-google',        'color' => 'info',      'sort_order' => 2, 'is_system' => true],
            ['key' => 'fna',   'label' => 'FNA (Facebook Network Appliance)',    'icon' => 'fab fa-facebook',      'color' => 'primary',   'sort_order' => 3, 'is_system' => true],
            ['key' => 'cdn',   'label' => 'CDN (Content Delivery Network)',      'icon' => 'fas fa-cloud',         'color' => 'purple',    'sort_order' => 4, 'is_system' => true],
            ['key' => 'bdix',  'label' => 'BDIX (Bangladesh Internet Exchange)', 'icon' => 'fas fa-network-wired', 'color' => 'success',   'sort_order' => 5, 'is_system' => true],
            ['key' => 'cache', 'label' => 'Private Cache / Mirror',             'icon' => 'fas fa-hdd',           'color' => 'warning',   'sort_order' => 6, 'is_system' => true],
        ];
        foreach ($bandwidthCategories as $cat) {
            if (!DB::table('bandwidth_categories')
                ->where('company_id', $companyId)
                ->where('key', $cat['key'])
                ->exists()) {
                DB::table('bandwidth_categories')->insert(array_merge($cat, [
                    'company_id' => $companyId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
        // Default Zone — required for organized client management
        $hasZone = DB::table('zones')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();
        if (!$hasZone) {
            DB::table('zones')->insert([
                'name'       => 'Main Zone',
                'is_active'  => true,
                'company_id' => $companyId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
