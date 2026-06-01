<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_user_type_check CHECK (user_type::text = ANY (ARRAY['super_admin','admin','employee','client','reseller','technician','sub_reseller','manager','card_distributor']::text[]))");

        $companyName = 'Default ISP';
        try {
            $existing = DB::table('system_settings')->where('key', 'company_name')->first();
            if ($existing) {
                $companyName = $existing->value ?: 'Default ISP';
            }
        } catch (\Exception $e) {}

        $companyId = DB::table('companies')->insertGetId([
            'name' => $companyName,
            'slug' => 'default',
            'email' => 'admin@isp.com',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tables = [
            'users', 'clients', 'zones', 'areas', 'boxes', 'packages',
            'bills', 'bill_items', 'payments', 'mikrotik_devices', 'employees',
            'resellers', 'tickets', 'ticket_categories', 'ticket_messages',
            'announcements', 'sms_settings', 'sms_templates', 'sms_logs',
            'new_connection_applications', 'company_profiles', 'system_settings',
            'branches', 'departments', 'designations', 'salaries', 'salary_payments',
            'attendances', 'leaves', 'expenses', 'expense_heads', 'incomes',
            'income_heads', 'fund_transfers', 'banks', 'accounts', 'vendors',
            'vendor_ledgers', 'tasks', 'collections', 'collection_transfers',
            'staff_submissions', 'staff_dues', 'advance_payments', 'ip_pools',
            'ip_addresses', 'vpn_accounts', 'scratch_card_batches', 'scratch_cards',
            'card_distributors', 'client_documents', 'client_additional_payments',
            'client_reconnections', 'client_line_shifts', 'client_status_logs',
            'client_activity_logs', 'client_recovery_logs', 'pops', 'splitters',
            'olt_devices', 'onus', 'onu_devices', 'olt_ports', 'products',
            'product_categories', 'instruments', 'instrument_categories',
            'daily_cash_closes', 'month_closes', 'activity_logs', 'audit_logs',
            'login_logs', 'role_permissions', 'menu_permissions',
            'automation_settings', 'due_reminders', 'whatsapp_settings',
            'whatsapp_templates', 'voice_call_settings', 'reseller_commissions',
            'reseller_payments', 'reseller_wallets', 'reseller_packages',
            'manager_assignments', 'technician_assignments', 'bill_corrections',
            'payment_methods', 'promo_codes', 'discounts',
            'knowledge_base_categories', 'knowledge_base_articles',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                DB::table($tableName)->whereNull('company_id')->update(['company_id' => $companyId]);
            }
        }

        $superAdmin = DB::table('users')->where('email', 'superadmin@isp.com')->first();
        if ($superAdmin) {
            DB::table('users')->where('id', $superAdmin->id)->update([
                'user_type' => 'super_admin',
                'company_id' => null,
            ]);
        } else {
            DB::table('users')->insert([
                'name' => 'Super Admin',
                'email' => 'superadmin@isp.com',
                'password' => Hash::make('SuperAdmin@123'),
                'user_type' => 'super_admin',
                'company_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('user_type', 'super_admin')->delete();
        DB::table('companies')->where('slug', 'default')->delete();
    }
};
