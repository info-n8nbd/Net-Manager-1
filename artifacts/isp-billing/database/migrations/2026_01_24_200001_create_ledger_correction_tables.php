<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable ledger entries - never deleted, only reversed
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no', 50)->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('entry_type', ['bill', 'payment', 'adjustment', 'reversal', 'correction', 'opening_balance']);
            $table->enum('debit_credit', ['debit', 'credit']); // debit = increases due, credit = decreases due
            $table->decimal('amount', 12, 2);
            $table->decimal('running_balance', 12, 2)->default(0);
            $table->string('reference_type', 50)->nullable(); // Bill, Payment, BillCorrection, PaymentCorrection
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 500);
            $table->date('entry_date');
            $table->string('billing_month', 7)->nullable(); // Format: 2026-01
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('created_by_role', 50)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->boolean('is_reversal')->default(false);
            $table->unsignedBigInteger('reverses_entry_id')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'entry_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('billing_month');
        });

        // Bill corrections - track all bill modifications
        Schema::create('bill_corrections', function (Blueprint $table) {
            $table->id();
            $table->string('correction_no', 50)->unique();
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('correction_type', ['amount_correction', 'cancellation', 'replacement']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Before values (snapshot)
            $table->decimal('original_package_amount', 12, 2);
            $table->decimal('original_discount', 12, 2)->default(0);
            $table->decimal('original_total_amount', 12, 2);
            $table->decimal('original_due_amount', 12, 2);
            $table->string('original_status', 20);
            
            // After values (for amount corrections)
            $table->decimal('new_package_amount', 12, 2)->nullable();
            $table->decimal('new_discount', 12, 2)->nullable();
            $table->decimal('new_total_amount', 12, 2)->nullable();
            $table->decimal('adjustment_amount', 12, 2)->default(0); // Difference to apply
            
            // For replacement - link to new bill
            $table->unsignedBigInteger('replacement_bill_id')->nullable();
            
            $table->text('reason'); // Mandatory reason
            $table->text('admin_notes')->nullable();
            
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['bill_id', 'created_at']);
            $table->index(['client_id', 'status']);
        });

        // Payment corrections - track all payment modifications  
        Schema::create('payment_corrections', function (Blueprint $table) {
            $table->id();
            $table->string('correction_no', 50)->unique();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('correction_type', ['duplicate', 'reversal', 'amount_correction', 'reallocation']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Before values (snapshot)
            $table->decimal('original_amount', 12, 2);
            $table->unsignedBigInteger('original_bill_id')->nullable();
            $table->string('original_transaction_id', 100)->nullable();
            
            // After values
            $table->decimal('new_amount', 12, 2)->nullable();
            $table->unsignedBigInteger('new_bill_id')->nullable();
            $table->decimal('reversal_amount', 12, 2)->default(0);
            
            // Duplicate detection info
            $table->unsignedBigInteger('duplicate_of_payment_id')->nullable();
            $table->string('duplicate_reason', 255)->nullable();
            
            $table->text('reason'); // Mandatory reason
            $table->text('admin_notes')->nullable();
            
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['payment_id', 'created_at']);
            $table->index(['client_id', 'status']);
        });

        // Request tokens for double-submit protection
        Schema::create('request_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('action_type', 50); // bill_submit, payment_collect, correction
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['token', 'used']);
            $table->index('expires_at');
        });

        // Audit log for all corrections
        Schema::create('correction_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type', 100); // BillCorrection, PaymentCorrection
            $table->unsignedBigInteger('auditable_id');
            $table->string('action', 50); // created, approved, rejected
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('performed_by_role', 50)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('performed_by');
        });

        // Add correction-related fields to bills
        Schema::table('bills', function (Blueprint $table) {
            $table->boolean('is_corrected')->default(false)->after('status');
            $table->boolean('is_cancelled')->default(false)->after('is_corrected');
            $table->boolean('is_replaced')->default(false)->after('is_cancelled');
            $table->unsignedBigInteger('replaced_by_bill_id')->nullable()->after('is_replaced');
            $table->unsignedBigInteger('replaces_bill_id')->nullable()->after('replaced_by_bill_id');
        });

        // Add correction-related fields to payments
        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('is_reversed')->default(false)->after('notes');
            $table->boolean('is_duplicate')->default(false)->after('is_reversed');
            $table->unsignedBigInteger('duplicate_of_id')->nullable()->after('is_duplicate');
            $table->string('request_token', 64)->nullable()->after('duplicate_of_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['is_reversed', 'is_duplicate', 'duplicate_of_id', 'request_token']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['is_corrected', 'is_cancelled', 'is_replaced', 'replaced_by_bill_id', 'replaces_bill_id']);
        });

        Schema::dropIfExists('correction_audit_logs');
        Schema::dropIfExists('request_tokens');
        Schema::dropIfExists('payment_corrections');
        Schema::dropIfExists('bill_corrections');
        Schema::dropIfExists('ledger_entries');
    }
};
