<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('mikrotik_devices', 'status')) {
                $table->string('status')->default('unknown')->after('is_active');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'uptime')) {
                $table->string('uptime')->nullable()->after('status');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('uptime');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'model')) {
                $table->string('model')->nullable()->after('name');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'version')) {
                $table->string('version')->nullable()->after('model');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'cpu_load')) {
                $table->integer('cpu_load')->nullable()->after('version');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'memory_usage')) {
                $table->integer('memory_usage')->nullable()->after('cpu_load');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'total_clients')) {
                $table->integer('total_clients')->default(0)->after('memory_usage');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('description');
            }
            if (!Schema::hasColumn('mikrotik_devices', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_devices', function (Blueprint $table) {
            $table->dropColumn(['status', 'uptime', 'last_checked_at', 'model', 'version', 'cpu_load', 'memory_usage', 'total_clients', 'latitude', 'longitude']);
        });
    }
};
