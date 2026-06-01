<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('card_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('card_transactions', 'commission')) {
                $table->decimal('commission', 10, 2)->default(0)->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('card_transactions', function (Blueprint $table) {
            $table->dropColumn('commission');
        });
    }
};
