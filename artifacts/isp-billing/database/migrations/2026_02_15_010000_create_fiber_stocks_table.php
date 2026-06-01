<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fiber_stocks')) {
            Schema::create('fiber_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();

                $table->string('fiber_code')->unique(); // drum code
                $table->integer('core'); // 2,4,6,12
                $table->decimal('start_meter', 10, 2);
                $table->decimal('end_meter', 10, 2);
                $table->decimal('total_meter', 10, 2);
                $table->decimal('remaining_meter', 10, 2);

                $table->date('stock_date');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fiber_stocks');
    }
};
