<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrity_check_logs', function (Blueprint $table) {
            $table->id();
            $table->string('check_type', 50)->default('full_billing_integrity');
            $table->string('status', 20)->default('pending');
            $table->integer('clients_checked')->default(0);
            $table->integer('bills_checked')->default(0);
            $table->integer('issues_found')->default(0);
            $table->integer('auto_corrected')->default(0);
            $table->integer('requires_review')->default(0);
            $table->text('details')->nullable();
            $table->decimal('duration_seconds', 10, 2)->nullable();
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('status');
            $table->index('created_at');
            $table->index(['status', 'requires_review']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrity_check_logs');
    }
};
