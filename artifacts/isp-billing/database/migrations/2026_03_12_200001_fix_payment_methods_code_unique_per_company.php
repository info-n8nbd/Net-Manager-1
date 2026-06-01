<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropUnique('payment_methods_code_unique');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->unique(['code', 'company_id'], 'payment_methods_code_company_unique');
            });
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        try {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropUnique('payment_methods_code_company_unique');
                $table->unique('code', 'payment_methods_code_unique');
            });
        } catch (\Throwable $e) {}
    }
};
