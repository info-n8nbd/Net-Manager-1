<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            if (!Schema::hasColumn('bills', 'is_prorated')) {
                $table->boolean('is_prorated')->default(false)->after('status');
            }
            if (!Schema::hasColumn('bills', 'connection_date')) {
                $table->date('connection_date')->nullable()->after('is_prorated');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['is_prorated', 'connection_date']);
        });
    }
};
