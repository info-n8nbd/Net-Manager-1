<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['clients', 'new_connection_applications'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'onu_brand')) {
                    $table->string('onu_brand')->nullable()->after('onu_mac');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['clients', 'new_connection_applications'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (Schema::hasColumn($tbl, 'onu_brand')) {
                    $table->dropColumn('onu_brand');
                }
            });
        }
    }
};
