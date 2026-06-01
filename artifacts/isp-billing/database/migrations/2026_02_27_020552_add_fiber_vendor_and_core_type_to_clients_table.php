<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['clients', 'new_connection_applications'];
        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    if (!Schema::hasColumn($tbl, 'fiber_vendor')) {
                        $table->string('fiber_vendor')->nullable()->after('onu_mac');
                    }
                    if (!Schema::hasColumn($tbl, 'fiber_core_type')) {
                        $table->string('fiber_core_type')->nullable()->after('fiber_vendor');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['clients', 'new_connection_applications'];
        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    if (Schema::hasColumn($tbl, 'fiber_vendor')) $table->dropColumn('fiber_vendor');
                    if (Schema::hasColumn($tbl, 'fiber_core_type')) $table->dropColumn('fiber_core_type');
                });
            }
        }
    }
};
