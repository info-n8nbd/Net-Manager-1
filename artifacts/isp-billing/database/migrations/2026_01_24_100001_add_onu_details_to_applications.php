<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->string('onu_model')->nullable()->after('onu_required');
            $table->string('onu_serial')->nullable()->after('onu_model');
            $table->string('olt_port')->nullable()->after('onu_serial');
            $table->foreignId('olt_device_id')->nullable()->after('olt_port')->constrained('olt_devices')->nullOnDelete();
            $table->text('device_notes')->nullable()->after('cable_length_meters');
            $table->string('preferred_connection_date')->nullable()->after('device_notes');
            $table->string('preferred_time_slot')->nullable()->after('preferred_connection_date');
        });
    }

    public function down(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->dropForeign(['olt_device_id']);
            $table->dropColumn(['onu_model', 'onu_serial', 'olt_port', 'olt_device_id', 'device_notes', 'preferred_connection_date', 'preferred_time_slot']);
        });
    }
};
