<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('router_health_logs')) {
            Schema::create('router_health_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
                $table->decimal('cpu_load', 5, 2)->default(0);
                $table->decimal('memory_usage', 5, 2)->default(0);
                $table->decimal('temperature', 5, 2)->nullable();
                $table->string('uptime')->nullable();
                $table->string('status')->default('online');
                $table->timestamps();
                $table->index(['mikrotik_device_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('notification_queue')) {
            Schema::create('notification_queue', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->string('channel');
                $table->string('recipient_type');
                $table->unsignedBigInteger('recipient_id')->nullable();
                $table->string('recipient_phone')->nullable();
                $table->string('recipient_email')->nullable();
                $table->string('title');
                $table->text('message');
                $table->string('priority')->default('normal');
                $table->json('metadata')->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                $table->index(['status', 'priority', 'created_at']);
            });
        }

        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->text('url')->nullable();
                $table->string('method')->nullable();
                $table->json('data')->nullable();
                $table->timestamp('created_at');
                $table->index(['user_id', 'created_at']);
                $table->index(['action', 'created_at']);
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->json('changes')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
                $table->index(['model_type', 'model_id']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('payment_audit_trail')) {
            Schema::create('payment_audit_trail', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->string('action');
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->json('data')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
                $table->index(['payment_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('security_alerts')) {
            Schema::create('security_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('alert_type');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->text('description')->nullable();
                $table->string('severity')->default('medium');
                $table->string('status')->default('pending');
                $table->text('resolution_note')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'severity', 'created_at']);
            });
        }

        if (!Schema::hasTable('fraud_alerts')) {
            Schema::create('fraud_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('alert_type');
                $table->string('trx_id')->nullable();
                $table->text('description')->nullable();
                $table->string('severity')->default('medium');
                $table->string('status')->default('pending');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }

        if (!Schema::hasTable('onu_reboot_history')) {
            Schema::create('onu_reboot_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('onu_device_id');
                $table->string('reason')->nullable();
                $table->unsignedBigInteger('rebooted_by')->nullable();
                $table->string('status')->default('completed');
                $table->timestamps();
                $table->index(['onu_device_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('onu_alarms')) {
            Schema::create('onu_alarms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('onu_device_id');
                $table->string('alarm_type');
                $table->text('description')->nullable();
                $table->string('severity')->default('warning');
                $table->boolean('is_active')->default(true);
                $table->timestamp('detected_at')->nullable();
                $table->timestamp('cleared_at')->nullable();
                $table->timestamps();
                $table->index(['onu_device_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('cron_health_logs')) {
            Schema::create('cron_health_logs', function (Blueprint $table) {
                $table->id();
                $table->string('job_name');
                $table->string('status');
                $table->integer('execution_time_ms')->nullable();
                $table->integer('memory_usage_mb')->nullable();
                $table->integer('records_processed')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamps();
                $table->index(['job_name', 'created_at']);
            });
        }

        if (!Schema::hasTable('failed_job_retries')) {
            Schema::create('failed_job_retries', function (Blueprint $table) {
                $table->id();
                $table->string('job_class');
                $table->json('payload')->nullable();
                $table->integer('attempt_count')->default(0);
                $table->integer('max_attempts')->default(3);
                $table->text('last_error')->nullable();
                $table->timestamp('next_retry_at')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->index(['status', 'next_retry_at']);
            });
        }

        if (!Schema::hasTable('live_pending_approvals')) {
            Schema::create('live_pending_approvals', function (Blueprint $table) {
                $table->id();
                $table->string('approval_type');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('pending');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'approval_type']);
            });
        }

        if (!Schema::hasColumn('onu_devices', 'temperature')) {
            Schema::table('onu_devices', function (Blueprint $table) {
                $table->decimal('temperature', 5, 2)->nullable()->after('tx_power');
            });
        }

        if (!Schema::hasColumn('mikrotik_devices', 'last_sync')) {
            Schema::table('mikrotik_devices', function (Blueprint $table) {
                $table->timestamp('last_sync')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('router_health_logs');
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('payment_audit_trail');
        Schema::dropIfExists('security_alerts');
        Schema::dropIfExists('fraud_alerts');
        Schema::dropIfExists('onu_reboot_history');
        Schema::dropIfExists('onu_alarms');
        Schema::dropIfExists('cron_health_logs');
        Schema::dropIfExists('failed_job_retries');
        Schema::dropIfExists('live_pending_approvals');
    }
};
