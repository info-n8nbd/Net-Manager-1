<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fiber_usages')) {
            Schema::create('fiber_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fiber_stock_id')->constrained('fiber_stocks')->cascadeOnDelete();

                // Client relation (exists)
                if (Schema::hasTable('clients')) {
                    $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('client_id')->nullable();
                }

                // Technician relation (may not exist yet)
                if (Schema::hasTable('technicians')) {
                    $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('technician_id')->nullable();
                }

                $table->decimal('used_meter', 10, 2);
                $table->integer('core');
                $table->date('usage_date');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fiber_usages');
    }
};
