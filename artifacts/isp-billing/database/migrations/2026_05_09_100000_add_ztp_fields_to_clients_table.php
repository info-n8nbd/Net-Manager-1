<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('provision_status')->nullable()->default(null)->after('mikrotik_profile_name'); // pending, provisioning, success, failed, skipped
            $table->text('provision_log')->nullable()->after('provision_status');
            $table->timestamp('provision_attempted_at')->nullable()->after('provision_log');
            $table->boolean('ztp_enabled')->default(true)->after('provision_attempted_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['provision_status', 'provision_log', 'provision_attempted_at', 'ztp_enabled']);
        });
    }
};
