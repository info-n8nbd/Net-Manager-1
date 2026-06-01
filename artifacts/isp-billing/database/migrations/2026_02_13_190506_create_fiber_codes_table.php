<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fiber_codes')) return;
        Schema::create('fiber_codes', function (Blueprint $table) {
            $table->id();
            $table->string('fiber_code')->unique(); // FBR-SM-001
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fiber_type')->default('Single Mode');
            $table->integer('start_meter')->default(0);
            $table->integer('end_meter');
            $table->integer('total_meter');
            $table->integer('remaining_meter');
            $table->date('purchase_date')->nullable();
            $table->enum('status', ['active', 'finished'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiber_codes');
    }
};
