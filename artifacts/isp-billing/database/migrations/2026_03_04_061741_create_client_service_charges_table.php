<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_service_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('charge_type', 100);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('charge_date')->nullable();
            $table->string('money_receipt_no')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('sender_number')->nullable();
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_service_charges');
    }
};
