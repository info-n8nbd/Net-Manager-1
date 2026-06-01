<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('new_connection_applications', 'connection_medium')) {
                $table->string('connection_medium')->default('utp')->after('connection_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            if (Schema::hasColumn('new_connection_applications', 'connection_medium')) {
                $table->dropColumn('connection_medium');
            }
        });
    }
};
