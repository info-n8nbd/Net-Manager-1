<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'permanent_discount')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->decimal('permanent_discount', 12, 2)->default(0)->after('monthly_fee');
                $table->string('google_map_location')->nullable()->after('address');
                $table->boolean('mac_binding_enabled')->default(false)->after('mac_address');
                $table->integer('custom_billing_cycle')->nullable()->after('billing_date');
            });
        }

        Schema::create('client_line_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('old_address')->nullable();
            $table->string('new_address');
            $table->string('old_zone')->nullable();
            $table->string('new_zone')->nullable();
            $table->decimal('shift_charge', 12, 2)->default(0);
            $table->date('shift_date');
            $table->text('remarks')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('client_reconnections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->date('disconnect_date');
            $table->date('reconnect_date');
            $table->decimal('reconnection_fee', 12, 2)->default(0);
            $table->text('disconnect_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('package_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('current_package_id')->constrained('packages')->onDelete('cascade');
            $table->foreignId('requested_package_id')->constrained('packages')->onDelete('cascade');
            $table->date('request_date');
            $table->date('effective_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('client_remarks')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        if (!Schema::hasColumn('bills', 'advance_used')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->decimal('advance_used', 12, 2)->default(0)->after('discount');
                $table->boolean('auto_reconnect')->default(false)->after('status');
            });
        }

        Schema::create('collection_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_account_id')->constrained('accounts')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('transfer_date');
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        if (!Schema::hasColumn('tickets', 'technician_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('technician_id')->nullable()->after('assigned_to')->constrained('users')->onDelete('set null');
                $table->datetime('sla_deadline')->nullable()->after('status');
                $table->boolean('sla_breached')->default(false)->after('sla_deadline');
                $table->integer('satisfaction_rating')->nullable()->after('sla_breached');
                $table->text('satisfaction_feedback')->nullable()->after('satisfaction_rating');
                $table->boolean('is_reopened')->default(false)->after('satisfaction_feedback');
                $table->integer('reopen_count')->default(0)->after('is_reopened');
            });
        }

        Schema::create('ticket_sla_configs', function (Blueprint $table) {
            $table->id();
            $table->string('priority');
            $table->integer('response_hours');
            $table->integer('resolution_hours');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('technician_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
            $table->datetime('assigned_at');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'reassigned'])->default('assigned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasColumn('resellers', 'billing_type')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->enum('billing_type', ['postpaid', 'prepaid'])->default('postpaid')->after('wallet_balance');
            });
        }

        Schema::create('reseller_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description');
            $table->string('reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        if (!Schema::hasColumn('expenses', 'approval_status')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved')->after('attachment');
                $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            });
        }

        Schema::create('vendor_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['purchase', 'payment', 'return', 'adjustment']);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        if (!Schema::hasColumn('products', 'has_serial')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('has_serial')->default(false)->after('unit');
                $table->integer('warranty_months')->default(0)->after('has_serial');
            });
        }

        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('serial_number')->unique();
            $table->string('mac_address')->nullable();
            $table->enum('status', ['in_stock', 'sold', 'assigned', 'damaged', 'returned'])->default('in_stock');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('purchase_id')->nullable()->constrained()->onDelete('set null');
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment', 'damage', 'return']);
            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('damaged_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('serial_id')->nullable()->constrained('product_serials')->onDelete('set null');
            $table->integer('quantity')->default(1);
            $table->date('damage_date');
            $table->text('damage_reason');
            $table->enum('status', ['reported', 'claimed', 'written_off'])->default('reported');
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('employee_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->integer('tickets_assigned')->default(0);
            $table->integer('tickets_resolved')->default(0);
            $table->decimal('avg_resolution_time', 8, 2)->default(0);
            $table->integer('sla_met')->default(0);
            $table->integer('sla_breached')->default(0);
            $table->decimal('collection_amount', 15, 2)->default(0);
            $table->integer('collection_count')->default(0);
            $table->decimal('rating_score', 3, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'month', 'year']);
        });

        Schema::create('sms_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_template_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->enum('schedule_type', ['due_reminder', 'disconnect', 'reconnect', 'payment', 'birthday', 'custom']);
            $table->integer('days_before')->nullable();
            $table->integer('days_after')->nullable();
            $table->time('send_time')->default('10:00:00');
            $table->json('target_filter')->nullable();
            $table->boolean('is_active')->default(true);
            $table->datetime('last_run')->nullable();
            $table->datetime('next_run')->nullable();
            $table->timestamps();
        });

        Schema::create('due_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->integer('reminder_day');
            $table->boolean('sms_sent')->default(false);
            $table->datetime('sent_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('due_reminders');
        Schema::dropIfExists('sms_schedules');
        Schema::dropIfExists('employee_performances');
        Schema::dropIfExists('damaged_items');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_serials');
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('vendor_ledgers');
        Schema::dropIfExists('reseller_wallet_transactions');
        Schema::dropIfExists('technician_assignments');
        Schema::dropIfExists('ticket_sla_configs');
        Schema::dropIfExists('collection_transfers');
        Schema::dropIfExists('package_change_requests');
        Schema::dropIfExists('client_reconnections');
        Schema::dropIfExists('client_line_shifts');

        if (Schema::hasColumn('resellers', 'billing_type')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->dropColumn('billing_type');
            });
        }

        if (Schema::hasColumn('tickets', 'technician_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropForeign(['technician_id']);
                $table->dropColumn(['technician_id', 'sla_deadline', 'sla_breached', 'satisfaction_rating', 'satisfaction_feedback', 'is_reopened', 'reopen_count']);
            });
        }

        if (Schema::hasColumn('expenses', 'approval_status')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approval_status', 'approved_by', 'approved_at']);
            });
        }

        if (Schema::hasColumn('products', 'has_serial')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['has_serial', 'warranty_months']);
            });
        }

        if (Schema::hasColumn('bills', 'advance_used')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropColumn(['advance_used', 'auto_reconnect']);
            });
        }

        if (Schema::hasColumn('clients', 'permanent_discount')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn(['permanent_discount', 'google_map_location', 'mac_binding_enabled', 'custom_billing_cycle']);
            });
        }
    }
};
