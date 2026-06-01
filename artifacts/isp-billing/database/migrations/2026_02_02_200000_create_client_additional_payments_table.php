<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_additional_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('payment_type')->default('other');
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('receipt_no')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'payment_date']);
            $table->index('payment_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_additional_payments');
    }
};
