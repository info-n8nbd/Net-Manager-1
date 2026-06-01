<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            if (!Schema::hasColumn('resellers', 'area_name_en')) {
                $table->string('area_name_en')->nullable()->after('address');
            }
            if (!Schema::hasColumn('resellers', 'area_name_bn')) {
                $table->string('area_name_bn')->nullable()->after('area_name_en');
            }
            if (!Schema::hasColumn('resellers', 'auto_recharge')) {
                $table->boolean('auto_recharge')->default(false)->after('minimum_recharge_days');
            }
            if (!Schema::hasColumn('resellers', 'recharge_day_type')) {
                $table->string('recharge_day_type', 10)->default('multi')->after('auto_recharge');
            }
            if (!Schema::hasColumn('resellers', 'single_recharge_day')) {
                $table->integer('single_recharge_day')->nullable()->after('recharge_day_type');
            }
            if (!Schema::hasColumn('resellers', 'terminate_hour')) {
                $table->integer('terminate_hour')->default(0)->after('single_recharge_day');
            }
            if (!Schema::hasColumn('resellers', 'terminate_minute')) {
                $table->integer('terminate_minute')->default(5)->after('terminate_hour');
            }
            if (!Schema::hasColumn('resellers', 'serial_start_from')) {
                $table->integer('serial_start_from')->default(0)->after('terminate_minute');
            }
            if (!Schema::hasColumn('resellers', 'client_prefix')) {
                $table->string('client_prefix', 20)->nullable()->after('serial_start_from');
            }
            if (!Schema::hasColumn('resellers', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->after('client_prefix');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $cols = ['area_name_en', 'area_name_bn', 'auto_recharge', 'recharge_day_type',
                     'single_recharge_day', 'terminate_hour', 'terminate_minute',
                     'serial_start_from', 'client_prefix', 'agent_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('resellers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
