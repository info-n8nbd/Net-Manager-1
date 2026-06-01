<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Assign company_id=1 to all MikroTik devices that have no company assigned.
        // Without this, TenantAware scope filters them out for logged-in users,
        // preventing PPP secret creation when resellers add clients.
        DB::table('mikrotik_devices')
            ->whereNull('company_id')
            ->update(['company_id' => 1]);
    }

    public function down(): void
    {
        //
    }
};
