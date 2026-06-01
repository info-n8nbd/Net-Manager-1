<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("stocks", function (Blueprint $table) {
            if (!Schema::hasColumn("stocks", "company_id")) {
                $table->unsignedBigInteger("company_id")->nullable()->after("id");
                $table->index("company_id");
            }
        });

        Schema::table("instrument_out_logs", function (Blueprint $table) {
            if (!Schema::hasColumn("instrument_out_logs", "company_id")) {
                $table->unsignedBigInteger("company_id")->nullable()->after("id");
                $table->index("company_id");
            }
        });

        Schema::table("fiber_stocks", function (Blueprint $table) {
            if (!Schema::hasColumn("fiber_stocks", "company_id")) {
                $table->unsignedBigInteger("company_id")->nullable()->after("id");
                $table->index("company_id");
            }
        });
    }

    public function down(): void
    {
        Schema::table("stocks", function (Blueprint $table) {
            $table->dropColumn("company_id");
        });
        Schema::table("instrument_out_logs", function (Blueprint $table) {
            $table->dropColumn("company_id");
        });
        Schema::table("fiber_stocks", function (Blueprint $table) {
            $table->dropColumn("company_id");
        });
    }
};
