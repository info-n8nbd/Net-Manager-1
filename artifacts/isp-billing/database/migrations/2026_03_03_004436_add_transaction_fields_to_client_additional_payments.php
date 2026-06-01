<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_additional_payments', function (Blueprint $table) {
            $table->string('transaction_id', 100)->nullable()->after('money_receipt_no');
            $table->string('sender_number', 20)->nullable()->after('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_additional_payments', function (Blueprint $table) {
            $table->dropColumn(['transaction_id', 'sender_number']);
        });
    }
};
