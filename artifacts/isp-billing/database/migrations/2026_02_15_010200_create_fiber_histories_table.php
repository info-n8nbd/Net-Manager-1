<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fiber_histories')) {
            Schema::create('fiber_histories', function (Blueprint $table) {
                $table->id();

                $table->string('type'); // in / out
                $table->foreignId('fiber_stock_id')->constrained('fiber_stocks')->cascadeOnDelete();

                $table->decimal('meter', 10, 2);
                $table->string('details')->nullable();
                $table->date('date');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fiber_histories');
    }
};
