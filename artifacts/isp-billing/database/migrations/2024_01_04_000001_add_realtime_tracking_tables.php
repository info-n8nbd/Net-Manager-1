<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('onu_reboot_history')) {
            Schema::create('onu_reboot_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('onu_id');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('serial_number', 50)->nullable();
                $table->string('reboot_type', 30)->default('manual');
                $table->string('initiated_by', 30)->nullable();
                $table->unsignedBigInteger('initiated_by_user_id')->nullable();
                $table->text('reason')->nullable();
                $table->string('result', 20)->default('pending');
                $table->text('result_message')->nullable();
                $table->timestamp('rebooted_at')->nullable();
                $table->timestamps();
                $table->index(['onu_id', 'created_at']);
                $table->index('client_id');
            });
        }

        if (!Schema::hasTable('onu_alarm_logs')) {
            Schema::create('onu_alarm_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('olt_device_id');
                $table->unsignedBigInteger('onu_id')->nullable();
                $table->string('serial_number', 50)->nullable();
                $table->string('alarm_type', 50);
                $table->string('severity', 20)->default('warning');
                $table->text('description')->nullable();
                $table->decimal('rx_power', 8, 2)->nullable();
                $table->decimal('tx_power', 8, 2)->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['olt_device_id', 'alarm_type', 'is_resolved']);
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('router_health_logs')) {
            Schema::create('router_health_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mikrotik_device_id');
                $table->decimal('cpu_load', 5, 2)->nullable();
                $table->decimal('memory_usage', 5, 2)->nullable();
                $table->decimal('temperature', 5, 2)->nullable();
                $table->integer('uptime_seconds')->nullable();
                $table->integer('active_sessions')->nullable();
                $table->string('status', 20)->default('online');
                $table->timestamps();
                $table->index(['mikrotik_device_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('cron_health_logs')) {
            Schema::create('cron_health_logs', function (Blueprint $table) {
                $table->id();
                $table->string('job_name', 100);
                $table->string('status', 20)->default('success');
                $table->integer('execution_time_ms')->nullable();
                $table->integer('records_processed')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['job_name', 'created_at']);
                $table->index('status');
            });
        }

        if (!Schema::hasTable('network_event_logs')) {
            Schema::create('network_event_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 50);
                $table->string('severity', 20)->default('info');
                $table->unsignedBigInteger('mikrotik_device_id')->nullable();
                $table->unsignedBigInteger('olt_device_id')->nullable();
                $table->unsignedBigInteger('zone_id')->nullable();
                $table->unsignedBigInteger('area_id')->nullable();
                $table->text('description')->nullable();
                $table->json('affected_clients')->nullable();
                $table->integer('affected_count')->default(0);
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
                $table->index(['event_type', 'is_resolved']);
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('client_reconnect_logs')) {
            Schema::create('client_reconnect_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('action', 30);
                $table->string('trigger', 50);
                $table->string('result', 20)->default('success');
                $table->text('details')->nullable();
                $table->string('previous_status', 20)->nullable();
                $table->string('new_status', 20)->nullable();
                $table->timestamps();
                $table->index(['client_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('ticket_sla_timers')) {
            Schema::create('ticket_sla_timers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id');
                $table->string('sla_type', 30);
                $table->integer('target_minutes');
                $table->timestamp('started_at');
                $table->timestamp('paused_at')->nullable();
                $table->integer('paused_minutes')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->boolean('breached')->default(false);
                $table->timestamp('breached_at')->nullable();
                $table->timestamps();
                $table->index(['ticket_id', 'sla_type']);
            });
        }

        if (!Schema::hasTable('real_time_alerts')) {
            Schema::create('real_time_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('alert_type', 50);
                $table->string('severity', 20)->default('info');
                $table->string('title');
                $table->text('message')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('zone_id')->nullable();
                $table->unsignedBigInteger('device_id')->nullable();
                $table->string('device_type', 30)->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_read')->default(false);
                $table->boolean('is_dismissed')->default(false);
                $table->string('target_role', 30)->nullable();
                $table->unsignedBigInteger('target_user_id')->nullable();
                $table->timestamps();
                $table->index(['alert_type', 'is_read', 'created_at']);
                $table->index('target_user_id');
            });
        }

        if (!Schema::hasTable('revenue_leakage_alerts')) {
            Schema::create('revenue_leakage_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('alert_type', 50);
                $table->decimal('expected_amount', 12, 2)->nullable();
                $table->decimal('actual_amount', 12, 2)->nullable();
                $table->decimal('difference', 12, 2)->nullable();
                $table->text('details')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
                $table->index(['client_id', 'is_resolved']);
                $table->index('alert_type');
            });
        }

        $clientColumns = [
            'last_seen_at' => fn($t) => $t->timestamp('last_seen_at')->nullable(),
            'current_rx_speed' => fn($t) => $t->decimal('current_rx_speed', 12, 2)->nullable(),
            'current_tx_speed' => fn($t) => $t->decimal('current_tx_speed', 12, 2)->nullable(),
            'session_uptime_seconds' => fn($t) => $t->bigInteger('session_uptime_seconds')->nullable(),
            'today_usage_bytes' => fn($t) => $t->bigInteger('today_usage_bytes')->default(0),
            'month_usage_bytes' => fn($t) => $t->bigInteger('month_usage_bytes')->default(0),
        ];
        foreach ($clientColumns as $col => $addFn) {
            if (!Schema::hasColumn('clients', $col)) {
                Schema::table('clients', function (Blueprint $table) use ($addFn) { $addFn($table); });
            }
        }

        if (Schema::hasTable('mikrotik_devices')) {
            $mikrotikCols = [
                'last_cpu_load' => fn($t) => $t->decimal('last_cpu_load', 5, 2)->nullable(),
                'last_memory_usage' => fn($t) => $t->decimal('last_memory_usage', 5, 2)->nullable(),
                'last_temperature' => fn($t) => $t->decimal('last_temperature', 5, 2)->nullable(),
                'last_uptime_seconds' => fn($t) => $t->integer('last_uptime_seconds')->nullable(),
                'last_active_sessions' => fn($t) => $t->integer('last_active_sessions')->nullable(),
                'last_down_at' => fn($t) => $t->timestamp('last_down_at')->nullable(),
                'last_up_at' => fn($t) => $t->timestamp('last_up_at')->nullable(),
            ];
            foreach ($mikrotikCols as $col => $addFn) {
                if (!Schema::hasColumn('mikrotik_devices', $col)) {
                    Schema::table('mikrotik_devices', function (Blueprint $table) use ($addFn) { $addFn($table); });
                }
            }
        }

        if (Schema::hasTable('olt_devices')) {
            $oltCols = [
                'total_onus' => fn($t) => $t->integer('total_onus')->default(0),
                'online_onus' => fn($t) => $t->integer('online_onus')->default(0),
                'offline_onus' => fn($t) => $t->integer('offline_onus')->default(0),
                'los_count' => fn($t) => $t->integer('los_count')->default(0),
                'last_poll_at' => fn($t) => $t->timestamp('last_poll_at')->nullable(),
            ];
            foreach ($oltCols as $col => $addFn) {
                if (!Schema::hasColumn('olt_devices', $col)) {
                    Schema::table('olt_devices', function (Blueprint $table) use ($addFn) { $addFn($table); });
                }
            }
        }

        if (Schema::hasTable('tickets')) {
            $ticketCols = [
                'sla_response_due_at' => fn($t) => $t->timestamp('sla_response_due_at')->nullable(),
                'sla_resolution_due_at' => fn($t) => $t->timestamp('sla_resolution_due_at')->nullable(),
                'sla_response_breached' => fn($t) => $t->boolean('sla_response_breached')->default(false),
                'sla_resolution_breached' => fn($t) => $t->boolean('sla_resolution_breached')->default(false),
                'first_response_at' => fn($t) => $t->timestamp('first_response_at')->nullable(),
                'escalation_level' => fn($t) => $t->integer('escalation_level')->default(0),
            ];
            foreach ($ticketCols as $col => $addFn) {
                if (!Schema::hasColumn('tickets', $col)) {
                    Schema::table('tickets', function (Blueprint $table) use ($addFn) { $addFn($table); });
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onu_reboot_history');
        Schema::dropIfExists('onu_alarm_logs');
        Schema::dropIfExists('router_health_logs');
        Schema::dropIfExists('cron_health_logs');
        Schema::dropIfExists('network_event_logs');
        Schema::dropIfExists('client_reconnect_logs');
        Schema::dropIfExists('ticket_sla_timers');
        Schema::dropIfExists('real_time_alerts');
        Schema::dropIfExists('revenue_leakage_alerts');
    }
};
