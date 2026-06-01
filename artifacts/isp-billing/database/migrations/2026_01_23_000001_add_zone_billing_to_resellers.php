<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            if (!Schema::hasColumn('resellers', 'zone_id')) {
                $table->foreignId('zone_id')->nullable()->after('address')->constrained('zones')->onDelete('set null');
            }
            if (!Schema::hasColumn('resellers', 'billing_type')) {
                $table->enum('billing_type', ['prepaid', 'postpaid'])->default('postpaid')->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
            $table->dropColumn('billing_type');
        });
    }
};
