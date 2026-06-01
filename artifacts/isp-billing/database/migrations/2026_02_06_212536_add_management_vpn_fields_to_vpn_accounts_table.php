<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vpn_accounts', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('account_type')->default('client')->after('name');
            $table->string('server_address')->nullable()->after('remote_address');
        });

        if (Schema::hasColumn('vpn_accounts', 'client_id')) {
            Schema::table('vpn_accounts', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });
            Schema::table('vpn_accounts', function (Blueprint $table) {
                $table->bigInteger('client_id')->nullable()->change();
                $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('mikrotik_devices', 'system_identity')) {
            Schema::table('mikrotik_devices', function (Blueprint $table) {
                $table->string('system_identity')->nullable()->after('model');
            });
        }
    }

    public function down(): void
    {
        Schema::table('vpn_accounts', function (Blueprint $table) {
            $table->dropColumn(['name', 'account_type', 'server_address']);
        });

        if (Schema::hasColumn('mikrotik_devices', 'system_identity')) {
            Schema::table('mikrotik_devices', function (Blueprint $table) {
                $table->dropColumn('system_identity');
            });
        }
    }
};
