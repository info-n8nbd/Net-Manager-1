<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_additional_payments', function (Blueprint $table) {
            $table->string('money_receipt_no', 50)->nullable()->after('receipt_no');
        });
    }

    public function down(): void
    {
        Schema::table('client_additional_payments', function (Blueprint $table) {
            $table->dropColumn('money_receipt_no');
        });
    }
};
