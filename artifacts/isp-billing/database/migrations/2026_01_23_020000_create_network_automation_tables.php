<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mikrotik_sessions')) {
            Schema::create('mikrotik_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
                $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
                $table->string('username');
                $table->string('service')->nullable();
                $table->string('caller_id')->nullable();
                $table->string('address')->nullable();
                $table->bigInteger('uptime_seconds')->default(0);
                $table->bigInteger('bytes_in')->default(0);
                $table->bigInteger('bytes_out')->default(0);
                $table->string('rate_in')->nullable();
                $table->string('rate_out')->nullable();
                $table->enum('status', ['online', 'offline'])->default('online');
                $table->timestamp('last_seen')->nullable();
                $table->timestamps();
                $table->index(['username', 'mikrotik_device_id']);
            });
        }

        if (!Schema::hasTable('mikrotik_router_stats')) {
            Schema::create('mikrotik_router_stats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
                $table->integer('cpu_load')->default(0);
                $table->bigInteger('free_memory')->default(0);
                $table->bigInteger('total_memory')->default(0);
                $table->bigInteger('free_hdd')->default(0);
                $table->bigInteger('total_hdd')->default(0);
                $table->string('uptime')->nullable();
                $table->string('version')->nullable();
                $table->string('board_name')->nullable();
                $table->integer('temperature')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('network_event_logs')) {
            Schema::create('network_event_logs', function (Blueprint $table) {
                $table->id();
                $table->enum('event_type', ['session_connect', 'session_disconnect', 'user_disabled', 'user_enabled', 'onu_offline', 'onu_online', 'fiber_cut', 'los_alarm', 'area_outage', 'router_down', 'router_up', 'alert']);
                $table->string('source')->nullable();
                $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('mikrotik_device_id')->nullable()->constrained()->onDelete('set null');
                $table->unsignedBigInteger('olt_device_id')->nullable();
                $table->unsignedBigInteger('onu_device_id')->nullable();
                $table->string('username')->nullable();
                $table->text('message');
                $table->json('metadata')->nullable();
                $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info');
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['event_type', 'created_at']);
                $table->index('client_id');
            });
        }

        if (!Schema::hasTable('cron_job_logs')) {
            Schema::create('cron_job_logs', function (Blueprint $table) {
                $table->id();
                $table->string('job_name');
                $table->string('job_class')->nullable();
                $table->enum('status', ['started', 'completed', 'failed'])->default('started');
                $table->text('message')->nullable();
                $table->json('metadata')->nullable();
                $table->integer('records_processed')->default(0);
                $table->integer('execution_time_ms')->default(0);
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['job_name', 'created_at']);
            });
        }

        if (!Schema::hasTable('onu_devices')) {
            Schema::create('onu_devices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('olt_device_id');
                $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
                $table->string('onu_serial')->unique();
                $table->string('port');
                $table->string('ont_id')->nullable();
                $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown');
                $table->decimal('rx_power', 8, 2)->nullable();
                $table->decimal('tx_power', 8, 2)->nullable();
                $table->string('description')->nullable();
                $table->string('last_down_reason')->nullable();
                $table->timestamp('last_down_at')->nullable();
                $table->timestamp('last_seen')->nullable();
                $table->timestamps();
                $table->index(['olt_device_id', 'status']);
            });
        }

        if (!Schema::hasTable('onu_events')) {
            Schema::create('onu_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('onu_device_id');
                $table->enum('event_type', ['LOS', 'LOF', 'ONLINE', 'OFFLINE', 'POWER_LOW', 'POWER_CRITICAL']);
                $table->decimal('rx_power', 8, 2)->nullable();
                $table->text('message')->nullable();
                $table->timestamp('detected_at');
                $table->timestamps();
                $table->index(['onu_device_id', 'event_type', 'detected_at']);
            });
        }

        if (!Schema::hasTable('olt_ports')) {
            Schema::create('olt_ports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('olt_device_id');
                $table->string('port_no');
                $table->integer('total_onu')->default(0);
                $table->integer('online_onu')->default(0);
                $table->integer('offline_onu')->default(0);
                $table->timestamp('last_polled')->nullable();
                $table->timestamps();
                $table->unique(['olt_device_id', 'port_no']);
            });
        }

        if (!Schema::hasTable('revenue_leakage_alerts')) {
            Schema::create('revenue_leakage_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->enum('alert_type', ['unpaid_online', 'expired_online', 'duplicate_connection']);
                $table->decimal('amount_due', 12, 2)->default(0);
                $table->text('message');
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_leakage_alerts');
        Schema::dropIfExists('olt_ports');
        Schema::dropIfExists('onu_events');
        Schema::dropIfExists('onu_devices');
        Schema::dropIfExists('cron_job_logs');
        Schema::dropIfExists('network_event_logs');
        Schema::dropIfExists('mikrotik_router_stats');
        Schema::dropIfExists('mikrotik_sessions');
    }
};
