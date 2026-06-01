<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            
            $table->string('action_type', 50);
            $table->string('action_category', 50);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->json('action_data');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            
            $table->string('status', 20)->default('active');
            $table->boolean('is_overridden')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('overridden_at')->nullable();
            
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['technician_id', 'action_type']);
            $table->index(['client_id', 'action_type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['status', 'is_overridden']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_action_logs');
    }
};
