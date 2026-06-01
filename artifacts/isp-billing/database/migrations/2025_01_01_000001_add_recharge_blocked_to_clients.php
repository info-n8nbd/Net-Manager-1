<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('clients', 'recharge_blocked')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->boolean('recharge_blocked')->default(false)->after('is_locked');
            });
        }
    }
    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('recharge_blocked');
        });
    }
};
