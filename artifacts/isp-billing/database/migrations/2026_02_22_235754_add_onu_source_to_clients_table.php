<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'onu_source')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('onu_source', 20)->nullable()->after('onu_required');
            });
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('onu_source');
        });
    }
};
