<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            if (!Schema::hasColumn('instruments', 'sub_category')) {
                $table->string('sub_category')->nullable()->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            if (Schema::hasColumn('instruments', 'sub_category')) {
                $table->dropColumn('sub_category');
            }
        });
    }
};
