<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'gender')) {
                $table->string('gender', 20)->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('clients', 'mother_name')) {
                $table->string('mother_name')->nullable()->after('father_husband_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['gender', 'mother_name']);
        });
    }
};
