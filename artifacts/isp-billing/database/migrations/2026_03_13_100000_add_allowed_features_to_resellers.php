<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resellers') && !Schema::hasColumn('resellers', 'allowed_features')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->json('allowed_features')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('resellers', 'allowed_features')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->dropColumn('allowed_features');
            });
        }
    }
};
