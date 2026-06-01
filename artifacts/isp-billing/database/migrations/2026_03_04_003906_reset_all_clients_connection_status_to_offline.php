<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('clients')
            ->where('connection_status', 'online')
            ->update([
                'connection_status' => 'offline',
                'session_ip' => null,
                'session_mac' => null,
                'session_uptime' => null,
            ]);
    }

    public function down(): void
    {
    }
};
