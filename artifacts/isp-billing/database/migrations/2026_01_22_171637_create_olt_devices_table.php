<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('port')->default(23);
            $table->string('username');
            $table->string('password');
            $table->string('vendor')->default('huawei');
            $table->string('model')->nullable();
            $table->string('location')->nullable();
            $table->integer('total_ports')->default(8);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'olt_id')) {
                $table->foreignId('olt_id')->nullable()->constrained('olt_devices')->nullOnDelete();
            }
            if (!Schema::hasColumn('clients', 'onu_serial')) {
                $table->string('onu_serial')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_port')) {
                $table->string('onu_port')->nullable();
            }
            if (!Schema::hasColumn('clients', 'onu_signal')) {
                $table->decimal('onu_signal', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('clients', 'device_info')) {
                $table->string('device_info')->nullable();
            }
            if (!Schema::hasColumn('clients', 'uptime')) {
                $table->string('uptime')->nullable();
            }
            if (!Schema::hasColumn('clients', 'last_logout')) {
                $table->string('last_logout')->nullable();
            }
            if (!Schema::hasColumn('clients', 'download_speed')) {
                $table->string('download_speed')->nullable();
            }
            if (!Schema::hasColumn('clients', 'upload_speed')) {
                $table->string('upload_speed')->nullable();
            }
            if (!Schema::hasColumn('clients', 'download_usage')) {
                $table->string('download_usage')->nullable();
            }
            if (!Schema::hasColumn('clients', 'upload_usage')) {
                $table->string('upload_usage')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('olt_id');
            $table->dropColumn(['onu_serial', 'onu_port', 'onu_signal', 'device_info', 'uptime', 'last_logout', 'download_speed', 'upload_speed', 'download_usage', 'upload_usage']);
        });
        Schema::dropIfExists('olt_devices');
    }
};
