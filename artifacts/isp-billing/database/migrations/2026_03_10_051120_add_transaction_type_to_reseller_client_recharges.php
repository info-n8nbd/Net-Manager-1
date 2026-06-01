<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_client_recharges', function (Blueprint $table) {
            if (!Schema::hasColumn('reseller_client_recharges', 'transaction_type')) {
                $table->string('transaction_type', 50)->default('recharge')->after('status');
            }
            if (!Schema::hasColumn('reseller_client_recharges', 'daily_rate')) {
                $table->decimal('daily_rate', 10, 4)->nullable()->after('recharge_amount');
            }
            if (!Schema::hasColumn('reseller_client_recharges', 'remarks')) {
                $table->text('remarks')->nullable()->after('recharged_by');
            }
            if (!Schema::hasColumn('reseller_client_recharges', 'credited_amount')) {
                $table->decimal('credited_amount', 12, 2)->nullable()->after('daily_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reseller_client_recharges', function (Blueprint $table) {
            $table->dropColumn(['transaction_type', 'daily_rate', 'remarks', 'credited_amount']);
        });
    }
};
