<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Clean up any existing duplicates before adding constraint
        DB::statement("
            DELETE FROM onus
            WHERE id NOT IN (
                SELECT MIN(id) FROM onus
                WHERE pon_port IS NOT NULL AND onu_number IS NOT NULL
                GROUP BY olt_device_id, pon_port, onu_number
            )
            AND pon_port IS NOT NULL
            AND onu_number IS NOT NULL
        ");

        // Also normalize any stray 3-part pon_port formats (GPON0/1/1 -> GPON0/1)
        DB::statement("
            UPDATE onus
            SET pon_port = 'GPON0/' || SPLIT_PART(pon_port, '/', 3)
            WHERE pon_port ~ '^GPON[0-9]+/[0-9]+/[0-9]+$'
        ");

        // Add unique partial index: only enforced when pon_port and onu_number are set
        // (allows null values without conflict)
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS onus_olt_port_onu_unique
            ON onus (olt_device_id, pon_port, onu_number)
            WHERE pon_port IS NOT NULL AND onu_number IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS onus_olt_port_onu_unique");
    }
};
