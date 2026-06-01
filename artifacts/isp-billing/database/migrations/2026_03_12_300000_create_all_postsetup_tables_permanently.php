<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permanent migration for all tables previously only created by PostSetup.php.
 * Uses hasTable() guards — safe on fresh installs AND existing databases.
 * Tables: user_menu_permissions, sales, sale_items, olt_snmp_stats, olt_traffic_stats, client_schedules
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. user_menu_permissions
        if (!Schema::hasTable('user_menu_permissions')) {
            Schema::create('user_menu_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('menu_key');
                $table->string('submenu_key')->nullable();
                $table->string('action_key')->nullable();
                $table->string('override', 10);
                $table->unsignedBigInteger('company_id')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'menu_key', 'submenu_key']);
                $table->unique(['user_id', 'menu_key', 'submenu_key', 'action_key'], 'ump_unique');
            });
        }

        // 2. sales (POS sales table)
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('invoice_number')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                $table->string('customer_type')->default('walk_in');
                $table->date('sale_date');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax_percent', 5, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->decimal('paid', 12, 2)->default(0);
                $table->decimal('due', 12, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('company_id');
                $table->index('client_id');
                $table->index('status');
            });
        }

        // 3. sale_items
        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('sale_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('product_name');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('serial_number')->nullable();
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->timestamps();
                $table->index('sale_id');
                $table->index('product_id');
            });
        }

        // 4. olt_snmp_stats (OLT time-series health data)
        if (!Schema::hasTable('olt_snmp_stats')) {
            Schema::create('olt_snmp_stats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('olt_device_id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->float('cpu_usage')->nullable();
                $table->float('memory_usage')->nullable();
                $table->float('temperature')->nullable();
                $table->bigInteger('uptime_seconds')->nullable();
                $table->integer('total_onu')->default(0);
                $table->integer('online_onu')->default(0);
                $table->integer('offline_onu')->default(0);
                $table->float('avg_rx_power')->nullable();
                $table->timestamp('polled_at')->nullable();
                $table->timestamps();
                $table->index(['olt_device_id', 'polled_at']);
                $table->index('company_id');
            });
        }

        // 5. olt_traffic_stats (per-port OLT traffic data)
        if (!Schema::hasTable('olt_traffic_stats')) {
            Schema::create('olt_traffic_stats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('olt_device_id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->integer('port_no');
                $table->bigInteger('in_octets')->default(0);
                $table->bigInteger('out_octets')->default(0);
                $table->bigInteger('in_rate_bps')->default(0);
                $table->bigInteger('out_rate_bps')->default(0);
                $table->integer('total_onu')->default(0);
                $table->integer('online_onu')->default(0);
                $table->timestamp('polled_at')->nullable();
                $table->timestamps();
                $table->index(['olt_device_id', 'port_no', 'polled_at'], 'olt_traffic_device_port_poll');
                $table->index('company_id');
            });
        }

        // 6. client_schedules (scheduled future client actions)
        if (!Schema::hasTable('client_schedules')) {
            Schema::create('client_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('action');
                $table->timestamp('scheduled_at');
                $table->string('status')->default('pending');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('mikrotik_profile')->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->text('execution_result')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->timestamps();
                $table->index(['status', 'scheduled_at']);
                $table->index('client_id');
                $table->index('company_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_schedules');
        Schema::dropIfExists('olt_traffic_stats');
        Schema::dropIfExists('olt_snmp_stats');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('user_menu_permissions');
    }
};
