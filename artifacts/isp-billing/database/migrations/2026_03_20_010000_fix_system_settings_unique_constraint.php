<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        // Convert any NULL company_id rows to company_id=1
        // (these were seeded by RoleServiceProvider as global defaults)
        DB::table('system_settings')
            ->whereNull('company_id')
            ->update(['company_id' => 1]);

        // Drop the old single-column unique constraint
        try {
            DB::statement('ALTER TABLE system_settings DROP CONSTRAINT IF EXISTS system_settings_key_unique');
        } catch (\Exception $e) {
            // already dropped or doesn't exist
        }

        // Add composite unique constraint: one setting per key per company
        try {
            DB::statement('ALTER TABLE system_settings ADD CONSTRAINT system_settings_key_company_unique UNIQUE (key, company_id)');
        } catch (\Exception $e) {
            // already exists or data conflict — silently skip
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE system_settings DROP CONSTRAINT IF EXISTS system_settings_key_company_unique');
            DB::statement('ALTER TABLE system_settings ADD CONSTRAINT system_settings_key_unique UNIQUE (key)');
        } catch (\Exception $e) {
            // silently skip
        }
    }
};
