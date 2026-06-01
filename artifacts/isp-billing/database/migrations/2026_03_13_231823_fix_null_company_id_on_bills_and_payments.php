<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE bills
            SET company_id = (
                SELECT company_id FROM clients
                WHERE clients.id = bills.client_id
            )
            WHERE bills.company_id IS NULL
        ');

        DB::statement('
            UPDATE payments
            SET company_id = (
                SELECT company_id FROM clients
                WHERE clients.id = payments.client_id
            )
            WHERE payments.company_id IS NULL
        ');
    }

    public function down(): void
    {
    }
};
