<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('mikrotik_devices')->nullOnDelete();
            $table->string('action'); // enable, disable, create, update, delete
            $table->string('action_type'); // pppoe, hotspot, queue
            $table->string('username')->nullable();
            $table->string('router_ip')->nullable();
            $table->string('trigger_source'); // auto_suspend, auto_restore, payment, admin, manual, billing
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'success', 'failed', 'retrying'])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['trigger_source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_action_logs');
    }
};
