<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            ['purchases', 'company_id'],
            ['purchase_items', 'company_id'],
            ['product_categories', 'company_id'],
            ['user_menu_permissions', 'company_id'],
            ['user_menu_permissions', 'user_id'],
            ['sales', 'company_id'],
            ['sale_items', 'company_id'],
            ['client_schedules', 'company_id'],
            ['client_schedules', 'client_id'],
            ['olt_snmp_stats', 'company_id'],
            ['olt_traffic_stats', 'company_id'],
            ['auto_inactive_skip_dates', 'company_id'],
        ];

        foreach ($indexes as [$table, $column]) {
            $indexName = "{$table}_{$column}_index";
            try {
                DB::statement("CREATE INDEX IF NOT EXISTS \"{$indexName}\" ON \"{$table}\" (\"{$column}\")");
            } catch (\Exception $e) {
            }
        }
    }

    public function down(): void
    {
    }
};
