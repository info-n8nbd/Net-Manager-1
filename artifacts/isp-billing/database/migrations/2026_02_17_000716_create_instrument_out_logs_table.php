<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('instrument_out_logs')) {
            Schema::create('instrument_out_logs', function (Blueprint $table) {
                $table->id();
                $table->json('instrument_ids');
                $table->integer('quantity')->default(1);
                $table->string('reason');
                $table->string('issued_to')->nullable();
                $table->unsignedBigInteger('technician_id')->nullable();
                $table->text('remarks')->nullable();
                $table->date('out_date');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_out_logs');
    }
};
