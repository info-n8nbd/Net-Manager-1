<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('technician_action_log_id')->constrained('technician_action_logs')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            
            $table->string('override_type', 50);
            $table->string('action_category', 50);
            
            $table->json('before_values');
            $table->json('after_values');
            $table->json('correction_data')->nullable();
            
            $table->text('reason');
            $table->string('status', 20)->default('completed');
            
            $table->decimal('balance_adjustment', 12, 2)->default(0);
            $table->boolean('network_sync_required')->default(false);
            $table->boolean('network_sync_completed')->default(false);
            $table->timestamp('network_synced_at')->nullable();
            
            $table->string('ip_address', 45)->nullable();
            
            $table->timestamps();
            
            $table->index(['admin_id', 'override_type']);
            $table->index(['client_id', 'action_category']);
            $table->index('technician_action_log_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_overrides');
    }
};
