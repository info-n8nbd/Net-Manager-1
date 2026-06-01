<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'connection_type')) {
                $table->enum('connection_type', ['pppoe', 'queue', 'invoice'])->default('pppoe')->after('status');
            }
            if (!Schema::hasColumn('clients', 'nid_front_image')) {
                $table->string('nid_front_image')->nullable()->after('nid');
            }
            if (!Schema::hasColumn('clients', 'nid_back_image')) {
                $table->string('nid_back_image')->nullable()->after('nid_front_image');
            }
            if (!Schema::hasColumn('clients', 'google_map_lat')) {
                $table->decimal('google_map_lat', 10, 8)->nullable()->after('google_map_location');
            }
            if (!Schema::hasColumn('clients', 'google_map_lng')) {
                $table->decimal('google_map_lng', 11, 8)->nullable()->after('google_map_lat');
            }
            if (!Schema::hasColumn('clients', 'uplink_device_id')) {
                $table->foreignId('uplink_device_id')->nullable()->after('connection_type');
            }
            if (!Schema::hasColumn('clients', 'signup_fee')) {
                $table->decimal('signup_fee', 10, 2)->default(0)->after('monthly_fee');
            }
            if (!Schema::hasColumn('clients', 'signup_fee_paid')) {
                $table->boolean('signup_fee_paid')->default(false)->after('signup_fee');
            }
            if (!Schema::hasColumn('clients', 'payment_deadline')) {
                $table->integer('payment_deadline')->default(10)->after('billing_date');
            }
            if (!Schema::hasColumn('clients', 'auto_disconnect_days')) {
                $table->integer('auto_disconnect_days')->default(15)->after('payment_deadline');
            }
            if (!Schema::hasColumn('clients', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('remarks');
            }
            if (!Schema::hasColumn('clients', 'lock_reason')) {
                $table->string('lock_reason')->nullable()->after('is_locked');
            }
            if (!Schema::hasColumn('clients', 'lock_date')) {
                $table->date('lock_date')->nullable()->after('lock_reason');
            }
            if (!Schema::hasColumn('clients', 'queue_max_limit')) {
                $table->string('queue_max_limit')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('clients', 'queue_burst_limit')) {
                $table->string('queue_burst_limit')->nullable()->after('queue_max_limit');
            }
            if (!Schema::hasColumn('clients', 'youtube_bandwidth')) {
                $table->string('youtube_bandwidth')->nullable()->after('queue_burst_limit');
            }
            if (!Schema::hasColumn('clients', 'facebook_bandwidth')) {
                $table->string('facebook_bandwidth')->nullable()->after('youtube_bandwidth');
            }
            if (!Schema::hasColumn('clients', 'auto_inactive_note')) {
                $table->text('auto_inactive_note')->nullable()->after('connection_status');
            }
            if (!Schema::hasColumn('clients', 'last_inactive_date')) {
                $table->date('last_inactive_date')->nullable()->after('auto_inactive_note');
            }
            if (!Schema::hasColumn('clients', 'last_active_date')) {
                $table->date('last_active_date')->nullable()->after('last_inactive_date');
            }
            if (!Schema::hasColumn('clients', 'bill_calculation_type')) {
                $table->enum('bill_calculation_type', ['auto', 'manual'])->default('auto')->after('custom_billing_cycle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = [
                'connection_type', 'nid_front_image', 'nid_back_image', 'google_map_lat', 'google_map_lng',
                'uplink_device_id', 'signup_fee', 'signup_fee_paid', 'payment_deadline', 'auto_disconnect_days',
                'is_locked', 'lock_reason', 'lock_date', 'queue_max_limit', 'queue_burst_limit',
                'youtube_bandwidth', 'facebook_bandwidth', 'auto_inactive_note', 'last_inactive_date',
                'last_active_date', 'bill_calculation_type'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
