<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_status_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('client_status_logs', 'source')) {
                $table->string('source')->default('manual')->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_status_logs', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
