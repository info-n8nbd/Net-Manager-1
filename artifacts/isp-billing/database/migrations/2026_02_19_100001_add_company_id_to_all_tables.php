<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'users',
        'clients',
        'zones',
        'areas',
        'boxes',
        'packages',
        'bills',
        'bill_items',
        'payments',
        'mikrotik_devices',
        'employees',
        'resellers',
        'tickets',
        'ticket_categories',
        'ticket_messages',
        'announcements',
        'sms_settings',
        'sms_templates',
        'sms_logs',
        'new_connection_applications',
        'company_profiles',
        'system_settings',
        'branches',
        'departments',
        'designations',
        'salaries',
        'salary_payments',
        'attendances',
        'leaves',
        'expenses',
        'expense_heads',
        'incomes',
        'income_heads',
        'fund_transfers',
        'banks',
        'accounts',
        'vendors',
        'vendor_ledgers',
        'tasks',
        'collections',
        'collection_transfers',
        'staff_submissions',
        'staff_dues',
        'advance_payments',
        'ip_pools',
        'ip_addresses',
        'vpn_accounts',
        'scratch_card_batches',
        'scratch_cards',
        'card_distributors',
        'client_documents',
        'client_additional_payments',
        'client_reconnections',
        'client_line_shifts',
        'client_status_logs',
        'client_activity_logs',
        'client_recovery_logs',
        'pops',
        'splitters',
        'olt_devices',
        'onus',
        'onu_devices',
        'olt_ports',
        'products',
        'product_categories',
        'instruments',
        'instrument_categories',
        'daily_cash_closes',
        'month_closes',
        'activity_logs',
        'audit_logs',
        'login_logs',
        'role_permissions',
        'menu_permissions',
        'automation_settings',
        'due_reminders',
        'whatsapp_settings',
        'whatsapp_templates',
        'voice_call_settings',
        'reseller_commissions',
        'reseller_payments',
        'reseller_wallets',
        'reseller_packages',
        'manager_assignments',
        'technician_assignments',
        'bill_corrections',
        'payment_methods',
        'promo_codes',
        'discount' . 's',
        'knowledge_base_categories',
        'knowledge_base_articles',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                    $table->index('company_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropIndex([$tableName . '_company_id_index']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
