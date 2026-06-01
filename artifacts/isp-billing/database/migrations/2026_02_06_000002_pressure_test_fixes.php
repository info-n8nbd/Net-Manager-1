<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE scratch_cards ALTER COLUMN card_number TYPE varchar(50)");
        DB::statement("ALTER TABLE scratch_cards ALTER COLUMN pin TYPE varchar(255)");
        
        DB::statement("ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_status_check");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_status_check CHECK (status IN ('active', 'inactive', 'suspended', 'pending', 'deleted'))");
        
        if (!Schema::hasColumn('api_tokens', 'is_active')) {
            Schema::table('api_tokens', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }

        DB::statement("CREATE INDEX IF NOT EXISTS idx_clients_status ON clients(status)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_payments_bill_id ON payments(bill_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_payments_client_status ON payments(client_id, status)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_bills_client_month ON bills(client_id, billing_month, billing_year)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_bills_status ON bills(status)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_payments_date ON payments(payment_date)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_scratch_cards_card_number ON scratch_cards(card_number)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_scratch_cards_status ON scratch_cards(status)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS idx_clients_status");
        DB::statement("DROP INDEX IF EXISTS idx_payments_bill_id");
        DB::statement("DROP INDEX IF EXISTS idx_payments_client_status");
        DB::statement("DROP INDEX IF EXISTS idx_bills_client_month");
        DB::statement("DROP INDEX IF EXISTS idx_bills_status");
        DB::statement("DROP INDEX IF EXISTS idx_payments_date");
        DB::statement("DROP INDEX IF EXISTS idx_scratch_cards_card_number");
        DB::statement("DROP INDEX IF EXISTS idx_scratch_cards_status");
    }
};
