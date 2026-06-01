<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_client_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->integer('online_count')->default(0);
            $table->timestamp('recorded_at');
            $table->index(['company_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_client_history');
    }
};
