<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_session_logs')) {
            Schema::create('client_session_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('event_type');
                $table->string('ip_address')->nullable();
                $table->string('mac_address')->nullable();
                $table->string('nas_ip')->nullable();
                $table->string('session_id')->nullable();
                $table->bigInteger('bytes_in')->nullable();
                $table->bigInteger('bytes_out')->nullable();
                $table->integer('session_time')->nullable();
                $table->string('terminate_cause')->nullable();
                $table->timestamps();

                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
                $table->index('client_id');
                $table->index('event_type');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_session_logs');
    }
};
