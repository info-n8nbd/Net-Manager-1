<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('vpn_account_id')->nullable()->after('branch_id');
            $table->integer('www_port')->default(80)->after('port');
            $table->boolean('overwrite_ppp_comment')->default(true)->after('description');

            $table->foreign('vpn_account_id')->references('id')->on('vpn_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_devices', function (Blueprint $table) {
            $table->dropForeign(['vpn_account_id']);
            $table->dropColumn(['vpn_account_id', 'www_port', 'overwrite_ppp_comment']);
        });
    }
};
