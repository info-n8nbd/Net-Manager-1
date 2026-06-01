<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'session_ip')) {
                $table->string('session_ip')->nullable();
            }
            if (!Schema::hasColumn('clients', 'session_mac')) {
                $table->string('session_mac')->nullable();
            }
            if (!Schema::hasColumn('clients', 'olt_device_id')) {
                $table->unsignedBigInteger('olt_device_id')->nullable();
            }
            if (!Schema::hasColumn('clients', 'olt_port')) {
                $table->string('olt_port')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_model')) {
                $table->string('onu_model')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_status')) {
                $table->string('onu_status')->default('unknown');
            }
            if (!Schema::hasColumn('clients', 'onu_rx_power')) {
                $table->decimal('onu_rx_power', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_tx_power')) {
                $table->decimal('onu_tx_power', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_signal_quality')) {
                $table->string('onu_signal_quality')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_down_reason')) {
                $table->text('onu_down_reason')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_last_down')) {
                $table->timestamp('onu_last_down')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_last_check')) {
                $table->timestamp('onu_last_check')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_temperature')) {
                $table->decimal('onu_temperature', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_firmware')) {
                $table->string('onu_firmware')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_uptime_seconds')) {
                $table->integer('onu_uptime_seconds')->nullable();
            }
            if (!Schema::hasColumn('clients', 'mikrotik_device_id')) {
                $table->unsignedBigInteger('mikrotik_device_id')->nullable();
            }
            if (!Schema::hasColumn('clients', 'mikrotik_queue_id')) {
                $table->string('mikrotik_queue_id')->nullable();
            }
            if (!Schema::hasColumn('clients', 'total_upload_bytes')) {
                $table->bigInteger('total_upload_bytes')->default(0);
            }
            if (!Schema::hasColumn('clients', 'total_download_bytes')) {
                $table->bigInteger('total_download_bytes')->default(0);
            }
            if (!Schema::hasColumn('clients', 'bandwidth_last_updated')) {
                $table->timestamp('bandwidth_last_updated')->nullable();
            }
            if (!Schema::hasColumn('clients', 'session_uptime')) {
                $table->string('session_uptime')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $cols = ['session_ip', 'session_mac', 'olt_device_id', 'olt_port', 'onu_model', 'onu_status',
                'onu_rx_power', 'onu_tx_power', 'onu_signal_quality', 'onu_down_reason',
                'onu_last_down', 'onu_last_check', 'onu_temperature', 'onu_firmware', 'onu_uptime_seconds',
                'mikrotik_device_id', 'mikrotik_queue_id', 'total_upload_bytes', 'total_download_bytes', 
                'bandwidth_last_updated', 'session_uptime'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
