<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('automation_settings', 'min_due_for_inactive')) {
                $table->decimal('min_due_for_inactive', 10, 2)->default(0)->after('disconnect_time')
                    ->comment('Minimum due amount required before client is auto-inactivated. 0 = any due amount triggers inactive.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('automation_settings', function (Blueprint $table) {
            $table->dropColumn('min_due_for_inactive');
        });
    }
};
