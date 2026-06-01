<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("packages", function (Blueprint $table) {
            $table->decimal("btrc_amount", 10, 2)->nullable()->after("price")
                  ->comment("Monthly bill amount reported to BTRC (overrides client monthly_fee in BTRC export)");
        });
    }

    public function down(): void
    {
        Schema::table("packages", function (Blueprint $table) {
            $table->dropColumn("btrc_amount");
        });
    }
};
