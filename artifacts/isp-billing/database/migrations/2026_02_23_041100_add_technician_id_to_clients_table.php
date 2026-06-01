<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'technician_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('technician_id')->nullable()->after('zone_id');
                $table->index('technician_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['technician_id']);
            $table->dropColumn('technician_id');
        });
    }
};
