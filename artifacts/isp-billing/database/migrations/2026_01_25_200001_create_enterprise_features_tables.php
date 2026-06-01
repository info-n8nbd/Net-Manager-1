<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_debit_notes')) {
            Schema::create('credit_debit_notes', function (Blueprint $table) {
                $table->id();
                $table->string('note_no')->unique();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->enum('type', ['credit', 'debit']);
                $table->decimal('amount', 12, 2);
                $table->string('reason');
                $table->text('description')->nullable();
                $table->integer('billing_month');
                $table->integer('billing_year');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->foreignId('ledger_entry_id')->nullable()->constrained('ledger_entries')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['client_id', 'billing_month', 'billing_year']);
                $table->index(['type', 'status']);
            });
        }

        if (!Schema::hasTable('month_closes')) {
            Schema::create('month_closes', function (Blueprint $table) {
                $table->id();
                $table->integer('billing_month');
                $table->integer('billing_year');
                $table->enum('status', ['open', 'closed', 'locked'])->default('open');
                $table->decimal('total_billed', 14, 2)->default(0);
                $table->decimal('total_collected', 14, 2)->default(0);
                $table->decimal('total_due', 14, 2)->default(0);
                $table->decimal('total_advance', 14, 2)->default(0);
                $table->integer('active_clients')->default(0);
                $table->integer('new_connections')->default(0);
                $table->integer('disconnections')->default(0);
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('summary_data')->nullable();
                $table->timestamps();
                
                $table->unique(['billing_month', 'billing_year']);
            });
        }

        if (!Schema::hasTable('daily_cash_closes')) {
            Schema::create('daily_cash_closes', function (Blueprint $table) {
                $table->id();
                $table->date('close_date');
                $table->foreignId('staff_user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('opening_balance', 12, 2)->default(0);
                $table->decimal('total_collection', 12, 2)->default(0);
                $table->decimal('total_submitted', 12, 2)->default(0);
                $table->decimal('closing_balance', 12, 2)->default(0);
                $table->decimal('cash_in_hand', 12, 2)->default(0);
                $table->decimal('discrepancy', 12, 2)->default(0);
                $table->enum('status', ['pending', 'verified', 'discrepancy'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                
                $table->unique(['close_date', 'staff_user_id']);
                $table->index(['staff_user_id', 'status']);
            });
        }

        if (!Schema::hasTable('system_health_logs')) {
            Schema::create('system_health_logs', function (Blueprint $table) {
                $table->id();
                $table->string('component');
                $table->enum('status', ['ok', 'warning', 'error']);
                $table->string('message');
                $table->integer('response_time_ms')->nullable();
                $table->json('details')->nullable();
                $table->timestamp('checked_at');
                $table->timestamps();
                
                $table->index(['component', 'checked_at']);
                $table->index('status');
            });
        }

        if (!Schema::hasTable('job_failure_logs')) {
            Schema::create('job_failure_logs', function (Blueprint $table) {
                $table->id();
                $table->string('job_name');
                $table->string('job_class');
                $table->json('payload')->nullable();
                $table->text('exception');
                $table->integer('attempts')->default(1);
                $table->integer('max_attempts')->default(3);
                $table->enum('status', ['failed', 'retrying', 'resolved', 'ignored'])->default('failed');
                $table->timestamp('retried_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
                
                $table->index(['status', 'created_at']);
                $table->index('job_name');
            });
        }

        if (!Schema::hasTable('action_confirmations')) {
            Schema::create('action_confirmations', function (Blueprint $table) {
                $table->id();
                $table->string('token')->unique();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('action_type');
                $table->string('action_description');
                $table->json('action_data');
                $table->enum('status', ['pending', 'confirmed', 'expired', 'cancelled'])->default('pending');
                $table->timestamp('expires_at');
                $table->timestamp('confirmed_at')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'status']);
                $table->index('expires_at');
            });
        }

        if (!Schema::hasTable('ip_restrictions')) {
            Schema::create('ip_restrictions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('role')->nullable();
                $table->string('ip_address');
                $table->enum('type', ['whitelist', 'blacklist']);
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
                
                $table->index(['type', 'is_active']);
                $table->index('ip_address');
            });
        }

        if (!Schema::hasTable('session_logs')) {
            Schema::create('session_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('session_id');
                $table->string('ip_address');
                $table->string('user_agent')->nullable();
                $table->string('device_type')->nullable();
                $table->timestamp('login_at');
                $table->timestamp('logout_at')->nullable();
                $table->timestamp('last_activity_at');
                $table->boolean('is_active')->default(true);
                $table->string('logout_reason')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'is_active']);
                $table->index('session_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('session_logs');
        Schema::dropIfExists('ip_restrictions');
        Schema::dropIfExists('action_confirmations');
        Schema::dropIfExists('job_failure_logs');
        Schema::dropIfExists('system_health_logs');
        Schema::dropIfExists('daily_cash_closes');
        Schema::dropIfExists('month_closes');
        Schema::dropIfExists('credit_debit_notes');
    }
};
