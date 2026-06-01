<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_serials')) return;
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('serial_no')->unique();
            $table->string('barcode')->nullable();
            $table->enum('status', ['stock', 'assigned', 'damaged'])->default('stock');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->date('warranty_expire_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_serials');
    }
};
