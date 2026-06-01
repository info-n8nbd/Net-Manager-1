<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fiber_usages')) return;
        Schema::create('fiber_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiber_code_id')->constrained()->cascadeOnDelete();
            $table->integer('used_meter');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->text('note')->nullable();
            $table->date('used_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiber_usages');
    }
};
