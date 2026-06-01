<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('network_event_logs')) {
            Schema::create('network_event_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type');
                $table->string('source');
                $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
                $table->text('message');
                $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
                $table->json('metadata')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamps();
                $table->index(['event_type', 'created_at']);
            });
        }

        if (!Schema::hasTable('onu_devices')) {
            Schema::create('onu_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('olt_device_id')->constrained()->onDelete('cascade');
                $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
                $table->string('onu_serial')->unique();
                $table->string('onu_mac')->nullable();
                $table->string('port')->nullable();
                $table->string('status')->default('online');
                $table->timestamp('last_seen')->nullable();
                $table->timestamp('last_down_at')->nullable();
                $table->decimal('rx_power', 10, 2)->nullable();
                $table->decimal('tx_power', 10, 2)->nullable();
                $table->integer('uptime_seconds')->default(0);
                $table->timestamps();
                $table->index(['olt_device_id', 'status']);
            });
        }

        if (!Schema::hasTable('onu_events')) {
            Schema::create('onu_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('onu_device_id')->constrained()->onDelete('cascade');
                $table->string('event_type');
                $table->text('message')->nullable();
                $table->timestamp('detected_at');
                $table->timestamps();
                $table->index(['onu_device_id', 'event_type']);
            });
        }

        if (!Schema::hasTable('olt_ports')) {
            Schema::create('olt_ports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('olt_device_id')->constrained()->onDelete('cascade');
                $table->string('port_name');
                $table->string('status')->default('active');
                $table->integer('total_onus')->default(0);
                $table->timestamp('last_polled')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cron_job_logs')) {
            Schema::create('cron_job_logs', function (Blueprint $table) {
                $table->id();
                $table->string('job_name');
                $table->string('job_class')->nullable();
                $table->enum('status', ['started', 'completed', 'failed'])->default('started');
                $table->text('message')->nullable();
                $table->integer('records_processed')->nullable();
                $table->integer('execution_time_ms')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['job_name', 'created_at']);
            });
        }

        if (!Schema::hasTable('due_reminders')) {
            Schema::create('due_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bill_id')->constrained()->onDelete('cascade');
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->text('message');
                $table->string('channel')->default('sms');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index(['bill_id', 'sent_at']);
            });
        }

        if (!Schema::hasTable('client_line_shifts')) {
            Schema::create('client_line_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->text('old_address');
                $table->text('new_address');
                $table->decimal('shift_charge', 12, 2)->default(0);
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('shift_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('client_reconnections')) {
            Schema::create('client_reconnections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->decimal('reconnection_fee', 12, 2)->default(0);
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('client_agreements', 'agreement_content')) {
            Schema::table('client_agreements', function (Blueprint $table) {
                $table->text('agreement_content')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_reconnections');
        Schema::dropIfExists('client_line_shifts');
        Schema::dropIfExists('due_reminders');
        Schema::dropIfExists('cron_job_logs');
        Schema::dropIfExists('olt_ports');
        Schema::dropIfExists('onu_events');
        Schema::dropIfExists('onu_devices');
        Schema::dropIfExists('network_event_logs');
    }
};
