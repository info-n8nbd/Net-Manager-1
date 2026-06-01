<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('onus')) {
            Schema::create('onus', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('olt_device_id');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('serial_number')->unique();
                $table->string('pon_port')->nullable();
                $table->integer('onu_number')->nullable();
                $table->string('model')->nullable();
                $table->string('description')->nullable();
                $table->string('status')->default('offline');
                $table->float('rx_power')->nullable();
                $table->float('tx_power')->nullable();
                $table->float('temperature')->nullable();
                $table->integer('uptime_seconds')->nullable();
                $table->string('signal_quality')->nullable();
                $table->string('mac_address')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('vlan')->nullable();
                $table->string('service_profile')->nullable();
                $table->string('line_profile')->nullable();
                $table->string('down_reason')->nullable();
                $table->timestamp('last_online')->nullable();
                $table->timestamp('last_offline')->nullable();
                $table->timestamp('provisioned_at')->nullable();
                $table->unsignedBigInteger('provisioned_by')->nullable();
                $table->boolean('is_provisioned')->default(false);
                $table->timestamps();
                $table->index('olt_device_id');
                $table->index('client_id');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('onu_provision_logs')) {
            Schema::create('onu_provision_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('onu_id')->nullable();
                $table->unsignedBigInteger('olt_device_id');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('serial_number');
                $table->string('action');
                $table->string('pon_port')->nullable();
                $table->integer('onu_number')->nullable();
                $table->string('service_profile')->nullable();
                $table->string('line_profile')->nullable();
                $table->string('vlan')->nullable();
                $table->string('result')->default('pending');
                $table->text('result_message')->nullable();
                $table->text('command_output')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('device_operation_logs')) {
            Schema::create('device_operation_logs', function (Blueprint $table) {
                $table->id();
                $table->string('device_type');
                $table->unsignedBigInteger('device_id');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('operation');
                $table->string('connection_type')->nullable();
                $table->json('parameters')->nullable();
                $table->string('result')->default('pending');
                $table->text('result_message')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
                $table->index(['device_type', 'device_id']);
                $table->index('client_id');
                $table->index('operation');
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('bandwidth_logs')) {
            Schema::create('bandwidth_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('mikrotik_device_id')->nullable();
                $table->bigInteger('rx_bytes')->default(0);
                $table->bigInteger('tx_bytes')->default(0);
                $table->integer('rx_rate')->default(0);
                $table->integer('tx_rate')->default(0);
                $table->string('session_uptime')->nullable();
                $table->date('log_date');
                $table->string('log_hour')->nullable();
                $table->timestamps();
                $table->index('client_id');
                $table->index('log_date');
                $table->index(['client_id', 'log_date']);
            });
        }

        if (!Schema::hasTable('radius_settings')) {
            Schema::create('radius_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('enabled')->default(false);
                $table->string('server_host')->default('127.0.0.1');
                $table->integer('auth_port')->default(1812);
                $table->integer('acct_port')->default(1813);
                $table->string('secret')->nullable();
                $table->string('nas_identifier')->nullable();
                $table->string('db_host')->default('127.0.0.1');
                $table->integer('db_port')->default(3306);
                $table->string('db_name')->default('radius');
                $table->string('db_user')->default('radius');
                $table->string('db_password')->nullable();
                $table->boolean('auto_sync_clients')->default(true);
                $table->boolean('auto_sync_packages')->default(true);
                $table->string('default_auth_type')->default('PAP');
                $table->json('extra_config')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('firewall_rules')) {
            Schema::create('firewall_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mikrotik_device_id');
                $table->string('chain');
                $table->string('action');
                $table->string('protocol')->nullable();
                $table->string('src_address')->nullable();
                $table->string('dst_address')->nullable();
                $table->string('src_port')->nullable();
                $table->string('dst_port')->nullable();
                $table->string('in_interface')->nullable();
                $table->string('out_interface')->nullable();
                $table->string('comment')->nullable();
                $table->boolean('disabled')->default(false);
                $table->string('mikrotik_id')->nullable();
                $table->integer('position')->nullable();
                $table->timestamps();
                $table->index('mikrotik_device_id');
            });
        }

        if (!Schema::hasTable('nat_rules')) {
            Schema::create('nat_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mikrotik_device_id');
                $table->string('chain')->default('srcnat');
                $table->string('action');
                $table->string('protocol')->nullable();
                $table->string('src_address')->nullable();
                $table->string('dst_address')->nullable();
                $table->string('src_port')->nullable();
                $table->string('dst_port')->nullable();
                $table->string('to_addresses')->nullable();
                $table->string('to_ports')->nullable();
                $table->string('in_interface')->nullable();
                $table->string('out_interface')->nullable();
                $table->string('comment')->nullable();
                $table->boolean('disabled')->default(false);
                $table->string('mikrotik_id')->nullable();
                $table->integer('position')->nullable();
                $table->timestamps();
                $table->index('mikrotik_device_id');
            });
        }

        if (!Schema::hasTable('ip_addresses')) {
            Schema::create('ip_addresses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ip_pool_id');
                $table->string('address');
                $table->string('status')->default('available');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('mac_address')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('ip_pool_id');
                $table->index('status');
                $table->unique('address');
            });
        }

        if (Schema::hasTable('ip_pools') && !Schema::hasColumn('ip_pools', 'mikrotik_device_id')) {
            Schema::table('ip_pools', function (Blueprint $table) {
                $table->unsignedBigInteger('mikrotik_device_id')->nullable()->after('id');
                $table->string('mikrotik_pool_name')->nullable()->after('name');
                $table->boolean('synced_to_router')->default(false)->after('status');
                $table->timestamp('last_synced_at')->nullable();
            });
        }

        if (!Schema::hasColumn('clients', 'onu_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('onu_id')->nullable();
            });
        }

        if (!Schema::hasColumn('olt_devices', 'snmp_community')) {
            Schema::table('olt_devices', function (Blueprint $table) {
                $table->string('snmp_community')->nullable()->default('public');
                $table->string('snmp_write_community')->nullable()->default('private');
                $table->integer('snmp_port')->nullable()->default(161);
                $table->boolean('snmp_enabled')->default(false);
                $table->unsignedBigInteger('pop_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
            });
        }

        if (!Schema::hasColumn('mikrotik_devices', 'pop_id')) {
            Schema::table('mikrotik_devices', function (Blueprint $table) {
                $table->unsignedBigInteger('pop_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onu_provision_logs');
        Schema::dropIfExists('onus');
        Schema::dropIfExists('device_operation_logs');
        Schema::dropIfExists('bandwidth_logs');
        Schema::dropIfExists('radius_settings');
        Schema::dropIfExists('firewall_rules');
        Schema::dropIfExists('nat_rules');
        Schema::dropIfExists('ip_addresses');
    }
};
