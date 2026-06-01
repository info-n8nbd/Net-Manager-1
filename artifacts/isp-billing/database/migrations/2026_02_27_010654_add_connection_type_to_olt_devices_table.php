<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('olt_devices', 'connection_type')) {
                $table->string('connection_type', 20)->default('telnet')->after('vendor');
            }
            if (!Schema::hasColumn('olt_devices', 'ssh_port')) {
                $table->integer('ssh_port')->nullable()->after('port');
            }
            if (!Schema::hasColumn('olt_devices', 'telnet_port')) {
                $table->integer('telnet_port')->nullable()->after('ssh_port');
            }
            if (!Schema::hasColumn('olt_devices', 'http_port')) {
                $table->integer('http_port')->nullable()->after('telnet_port');
            }
            if (!Schema::hasColumn('olt_devices', 'status')) {
                $table->string('status', 20)->default('unknown')->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $cols = ['connection_type', 'ssh_port', 'telnet_port', 'http_port', 'status'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('olt_devices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
