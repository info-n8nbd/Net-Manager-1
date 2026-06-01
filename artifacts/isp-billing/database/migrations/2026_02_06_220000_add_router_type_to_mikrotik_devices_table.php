<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('mikrotik_devices', 'router_type')) {
                $table->string('router_type')->default('core')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_devices', function (Blueprint $table) {
            $table->dropColumn(['router_type']);
        });
    }
};
