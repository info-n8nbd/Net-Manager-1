<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the global unique constraint on code
        // and replace with a compound unique (code, company_id)
        try {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->dropUnique('sms_templates_code_unique');
            });
        } catch (\Throwable $e) {
            // Already dropped or doesn't exist
        }

        try {
            Schema::table('sms_templates', function (Blueprint $table) {
                // Allow NULL codes (some templates may not have a code)
                $table->string('code')->nullable()->change();
                // Add compound unique: code + company_id (both must be non-null to conflict)
                $table->unique(['code', 'company_id'], 'sms_templates_code_company_unique');
            });
        } catch (\Throwable $e) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        try {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->dropUnique('sms_templates_code_company_unique');
                $table->unique('code', 'sms_templates_code_unique');
            });
        } catch (\Throwable $e) {}
    }
};
