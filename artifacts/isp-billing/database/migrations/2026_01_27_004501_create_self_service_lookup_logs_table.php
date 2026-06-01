<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('self_service_lookup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->string('identifier_searched', 100);
            $table->boolean('found')->default(false);
            $table->unsignedBigInteger('client_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('ip_address');
            $table->index('created_at');
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('self_service_lookup_logs');
    }
};
