<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            if (!Schema::hasColumn('onus', 'config_status')) {
                $table->string('config_status', 50)->nullable()->after('status');
            }
            if (!Schema::hasColumn('onus', 'olt_offline_reason')) {
                $table->string('olt_offline_reason', 100)->nullable()->after('last_down_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->dropColumnIfExists('config_status');
            $table->dropColumnIfExists('olt_offline_reason');
        });
    }
};
