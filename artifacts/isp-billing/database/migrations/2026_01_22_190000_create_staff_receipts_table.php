<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('received_by');
            $table->decimal('amount', 12, 2);
            $table->date('receipt_date');
            $table->string('payment_method')->default('cash');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['staff_id', 'receipt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_receipts');
    }
};
