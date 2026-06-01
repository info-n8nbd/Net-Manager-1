<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'occupation')) {
                $table->string('occupation')->nullable()->after('nid');
            }
            if (!Schema::hasColumn('clients', 'router_brand')) {
                $table->string('router_brand')->nullable()->after('onu_monthly_charge');
            }
        });

        Schema::table('router_health_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('router_health_logs', 'uptime')) {
                $table->string('uptime')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['occupation', 'router_brand']);
        });

        Schema::table('router_health_logs', function (Blueprint $table) {
            $table->dropColumn('uptime');
        });
    }
};
