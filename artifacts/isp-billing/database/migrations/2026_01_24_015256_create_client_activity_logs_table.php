<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('action_type'); // status_change, activated, deactivated, suspended, reconnected, bill_generated, payment_received
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('triggered_by')->default('manual'); // manual, system, auto
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'action_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_activity_logs');
    }
};
