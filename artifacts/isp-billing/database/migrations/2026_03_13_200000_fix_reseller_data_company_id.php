<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix reseller zones with company_id=NULL
        DB::statement("
            UPDATE zones z
            SET company_id = r.company_id
            FROM resellers r
            WHERE z.reseller_id = r.id
              AND z.company_id IS NULL
              AND r.company_id IS NOT NULL
        ");

        // Fix reseller areas with company_id=NULL
        DB::statement("
            UPDATE areas a
            SET company_id = r.company_id
            FROM resellers r
            WHERE a.reseller_id = r.id
              AND a.company_id IS NULL
              AND r.company_id IS NOT NULL
        ");

        // Fix reseller_packages with company_id=NULL
        DB::statement("
            UPDATE reseller_packages rp
            SET company_id = r.company_id
            FROM resellers r
            WHERE rp.reseller_id = r.id
              AND rp.company_id IS NULL
              AND r.company_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Not reversible - data fix only
    }
};
