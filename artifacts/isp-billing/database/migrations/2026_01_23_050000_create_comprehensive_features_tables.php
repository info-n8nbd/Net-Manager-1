<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('speed_test_logs')) {
            Schema::create('speed_test_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->decimal('download_speed', 10, 2);
                $table->decimal('upload_speed', 10, 2);
                $table->integer('ping_ms');
                $table->string('server_location')->nullable();
                $table->string('test_type')->default('manual');
                $table->timestamps();
                $table->index(['client_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('uptime_logs')) {
            Schema::create('uptime_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->date('log_date');
                $table->integer('uptime_minutes')->default(0);
                $table->integer('downtime_minutes')->default(0);
                $table->decimal('uptime_percent', 5, 2)->default(100);
                $table->json('downtime_periods')->nullable();
                $table->timestamps();
                $table->unique(['client_id', 'log_date']);
            });
        }

        if (!Schema::hasTable('slow_speed_alerts')) {
            Schema::create('slow_speed_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->decimal('expected_speed', 10, 2);
                $table->decimal('actual_speed', 10, 2);
                $table->string('alert_type')->default('slow');
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('speed_boost_requests')) {
            Schema::create('speed_boost_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->integer('duration_hours')->default(2);
                $table->decimal('boost_speed', 10, 2);
                $table->string('status')->default('pending');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bill_splits')) {
            Schema::create('bill_splits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bill_id')->constrained()->onDelete('cascade');
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->decimal('first_payment', 12, 2);
                $table->decimal('second_payment', 12, 2);
                $table->date('first_due_date');
                $table->date('second_due_date');
                $table->boolean('first_paid')->default(false);
                $table->boolean('second_paid')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('planned_maintenances')) {
            Schema::create('planned_maintenances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('scheduled_start');
                $table->timestamp('scheduled_end');
                $table->boolean('notification_sent')->default(false);
                $table->boolean('is_completed')->default(false);
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_ratings')) {
            Schema::create('service_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
                $table->integer('rating')->default(5);
                $table->text('feedback')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('technician_jobs')) {
            Schema::create('technician_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('ticket_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->string('job_type');
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
                $table->text('description')->nullable();
                $table->text('completion_notes')->nullable();
                $table->json('checklist')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('response_time_minutes')->nullable();
                $table->timestamps();
                $table->index(['technician_id', 'status']);
            });
        }

        if (!Schema::hasTable('job_photos')) {
            Schema::create('job_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('technician_job_id')->constrained()->onDelete('cascade');
                $table->string('photo_type');
                $table->string('file_path');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('technician_shifts')) {
            Schema::create('technician_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
                $table->date('shift_date');
                $table->timestamp('clock_in')->nullable();
                $table->timestamp('clock_out')->nullable();
                $table->integer('total_minutes')->nullable();
                $table->timestamps();
                $table->unique(['technician_id', 'shift_date']);
            });
        }

        if (!Schema::hasTable('device_inventory')) {
            Schema::create('device_inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
                $table->string('device_type');
                $table->string('serial_number')->unique();
                $table->string('model')->nullable();
                $table->enum('status', ['available', 'assigned', 'deployed', 'damaged', 'lost'])->default('available');
                $table->foreignId('deployed_to_client')->nullable()->constrained('clients')->onDelete('set null');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('client_grace_periods')) {
            Schema::create('client_grace_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->integer('grace_days')->default(5);
                $table->text('reason')->nullable();
                $table->foreignId('approved_by')->constrained('users')->onDelete('cascade');
                $table->date('valid_until')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('collection_targets')) {
            Schema::create('collection_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('collector_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
                $table->string('month');
                $table->decimal('target_amount', 15, 2);
                $table->decimal('achieved_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->unique(['collector_id', 'month']);
            });
        }

        if (!Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('description')->nullable();
                $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
                $table->decimal('discount_value', 12, 2);
                $table->decimal('max_discount', 12, 2)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('used_count')->default(0);
                $table->date('valid_from');
                $table->date('valid_until');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('client_promo_usage')) {
            Schema::create('client_promo_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->foreignId('promo_code_id')->constrained()->onDelete('cascade');
                $table->foreignId('bill_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('discount_applied', 12, 2);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('amount', 12, 2);
                $table->string('reason');
                $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('advance_payment_rewards')) {
            Schema::create('advance_payment_rewards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->integer('points')->default(0);
                $table->string('reward_type');
                $table->text('description')->nullable();
                $table->boolean('is_redeemed')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reseller_commissions')) {
            Schema::create('reseller_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('users')->onDelete('cascade');
                $table->string('month');
                $table->integer('total_clients')->default(0);
                $table->integer('new_clients')->default(0);
                $table->decimal('collection_amount', 15, 2)->default(0);
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->decimal('commission_amount', 15, 2)->default(0);
                $table->boolean('is_settled')->default(false);
                $table->timestamp('settled_at')->nullable();
                $table->timestamps();
                $table->unique(['reseller_id', 'month']);
            });
        }

        if (!Schema::hasTable('reseller_boundaries')) {
            Schema::create('reseller_boundaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('zone_id')->constrained()->onDelete('cascade');
                $table->boolean('is_exclusive')->default(false);
                $table->timestamps();
                $table->unique(['reseller_id', 'zone_id']);
            });
        }

        if (!Schema::hasTable('reseller_packages')) {
            Schema::create('reseller_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('package_id')->constrained()->onDelete('cascade');
                $table->decimal('custom_price', 12, 2)->nullable();
                $table->boolean('is_available')->default(true);
                $table->timestamps();
                $table->unique(['reseller_id', 'package_id']);
            });
        }

        if (!Schema::hasTable('internet_quality_scores')) {
            Schema::create('internet_quality_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->date('score_date');
                $table->integer('quality_score')->default(100);
                $table->decimal('avg_speed_percent', 5, 2)->default(100);
                $table->decimal('uptime_percent', 5, 2)->default(100);
                $table->integer('latency_score')->default(100);
                $table->integer('packet_loss_score')->default(100);
                $table->timestamps();
                $table->unique(['client_id', 'score_date']);
            });
        }

        if (!Schema::hasTable('churn_predictions')) {
            Schema::create('churn_predictions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->decimal('churn_probability', 5, 2);
                $table->json('risk_factors')->nullable();
                $table->string('recommended_action')->nullable();
                $table->boolean('action_taken')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('client_lifetime_values')) {
            Schema::create('client_lifetime_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->decimal('total_revenue', 15, 2)->default(0);
                $table->decimal('avg_monthly_revenue', 12, 2)->default(0);
                $table->integer('tenure_months')->default(0);
                $table->decimal('predicted_clv', 15, 2)->default(0);
                $table->string('segment')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('area_profitability')) {
            Schema::create('area_profitability', function (Blueprint $table) {
                $table->id();
                $table->foreignId('zone_id')->constrained()->onDelete('cascade');
                $table->string('month');
                $table->integer('total_clients')->default(0);
                $table->decimal('total_revenue', 15, 2)->default(0);
                $table->decimal('collection_rate', 5, 2)->default(0);
                $table->decimal('operating_cost', 15, 2)->default(0);
                $table->decimal('profit', 15, 2)->default(0);
                $table->integer('profit_rank')->nullable();
                $table->timestamps();
                $table->unique(['zone_id', 'month']);
            });
        }

        if (!Schema::hasTable('ticket_escalations')) {
            Schema::create('ticket_escalations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
                $table->foreignId('escalated_from')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('escalated_to')->nullable()->constrained('users')->onDelete('set null');
                $table->string('reason');
                $table->integer('escalation_level')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ticket_resolution_scores')) {
            Schema::create('ticket_resolution_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
                $table->integer('response_time_score')->default(0);
                $table->integer('resolution_time_score')->default(0);
                $table->integer('customer_satisfaction_score')->default(0);
                $table->integer('first_contact_resolution')->default(0);
                $table->integer('total_score')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mikrotik_realtime_sessions')) {
            Schema::create('mikrotik_realtime_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
                $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
                $table->string('session_id')->nullable();
                $table->string('username');
                $table->string('ip_address')->nullable();
                $table->string('mac_address')->nullable();
                $table->bigInteger('bytes_in')->default(0);
                $table->bigInteger('bytes_out')->default(0);
                $table->integer('uptime_seconds')->default(0);
                $table->timestamp('connected_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['mikrotik_device_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('mikrotik_bandwidth_usage')) {
            Schema::create('mikrotik_bandwidth_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->date('usage_date');
                $table->bigInteger('download_bytes')->default(0);
                $table->bigInteger('upload_bytes')->default(0);
                $table->decimal('peak_download_mbps', 10, 2)->default(0);
                $table->decimal('peak_upload_mbps', 10, 2)->default(0);
                $table->timestamps();
                $table->unique(['client_id', 'usage_date']);
            });
        }

        if (!Schema::hasColumn('clients', 'internet_quality_score')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->integer('internet_quality_score')->default(100)->after('connection_status');
            });
        }

        if (!Schema::hasColumn('clients', 'churn_risk_score')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->decimal('churn_risk_score', 5, 2)->default(0)->after('internet_quality_score');
            });
        }

        if (!Schema::hasColumn('clients', 'lifetime_value')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->decimal('lifetime_value', 15, 2)->default(0)->after('churn_risk_score');
            });
        }

        if (!Schema::hasColumn('tickets', 'auto_category')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('auto_category')->nullable()->after('category_id');
            });
        }
        if (!Schema::hasColumn('tickets', 'expected_fix_hours')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->integer('expected_fix_hours')->nullable()->after('auto_category');
            });
        }
        if (!Schema::hasColumn('tickets', 'escalation_level')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->integer('escalation_level')->default(0)->after('expected_fix_hours');
            });
        }

        if (!Schema::hasColumn('users', 'performance_score')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('performance_score')->default(0)->after('email');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'mikrotik_bandwidth_usage', 'mikrotik_realtime_sessions', 'ticket_resolution_scores',
            'ticket_escalations', 'area_profitability', 'client_lifetime_values', 'churn_predictions',
            'internet_quality_scores', 'reseller_packages', 'reseller_boundaries', 'reseller_commissions',
            'advance_payment_rewards', 'refunds', 'client_promo_usage', 'promo_codes',
            'collection_targets', 'client_grace_periods', 'device_inventory', 'technician_shifts',
            'job_photos', 'technician_jobs', 'service_ratings', 'planned_maintenances',
            'bill_splits', 'speed_boost_requests', 'slow_speed_alerts', 'uptime_logs', 'speed_test_logs'
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
