<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('new_connection_applications', 'fiber_code')) {
                $table->string('fiber_code')->nullable()->after('onu_serial');
            }
            if (!Schema::hasColumn('new_connection_applications', 'fiber_color')) {
                $table->string('fiber_color')->nullable()->after('fiber_code');
            }
            if (!Schema::hasColumn('new_connection_applications', 'splitter_number')) {
                $table->string('splitter_number')->nullable()->after('fiber_color');
            }
            if (!Schema::hasColumn('new_connection_applications', 'splitter_color')) {
                $table->string('splitter_color')->nullable()->after('splitter_number');
            }
            if (!Schema::hasColumn('new_connection_applications', 'onu_mac')) {
                $table->string('onu_mac')->nullable()->after('splitter_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $cols = ['fiber_code', 'fiber_color', 'splitter_number', 'splitter_color', 'onu_mac'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('new_connection_applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
