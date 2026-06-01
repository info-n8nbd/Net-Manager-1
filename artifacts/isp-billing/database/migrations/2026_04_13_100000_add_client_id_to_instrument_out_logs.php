<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('instrument_out_logs', 'client_id')) {
            Schema::table('instrument_out_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->after('issued_to')->index();
            });
        }

        if (!Schema::hasColumn('instruments', 'client_id')) {
            Schema::table('instruments', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->after('assigned_to')->index();
            });
        }

        DB::statement("
            UPDATE instrument_out_logs
            SET client_id = issued_to::BIGINT
            WHERE issued_to ~ '^[0-9]+$'
              AND client_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('instrument_out_logs', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });
        Schema::table('instruments', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });
    }
};
