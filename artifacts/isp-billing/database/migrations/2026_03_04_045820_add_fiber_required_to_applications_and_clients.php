<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->boolean('fiber_required')->default(true)->after('router_required');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('fiber_required')->default(true)->after('router_required');
        });
    }

    public function down(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->dropColumn('fiber_required');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('fiber_required');
        });
    }
};
