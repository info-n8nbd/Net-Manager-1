<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiber_stocks', function (Blueprint $table) {
            $table->string('brand_name')->nullable()->after('fiber_code');
        });
    }

    public function down(): void
    {
        Schema::table('fiber_stocks', function (Blueprint $table) {
            $table->dropColumn('brand_name');
        });
    }
};
