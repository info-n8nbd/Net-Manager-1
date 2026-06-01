<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'is_free')) {
                $table->boolean('is_free')->default(false)->after('status');
            }
            if (!Schema::hasColumn('clients', 'free_reason')) {
                $table->string('free_reason')->nullable()->after('is_free');
            }
            if (!Schema::hasColumn('clients', 'onu_monthly_charge')) {
                $table->decimal('onu_monthly_charge', 10, 2)->default(0)->after('free_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['is_free', 'free_reason', 'onu_monthly_charge']);
        });
    }
};
