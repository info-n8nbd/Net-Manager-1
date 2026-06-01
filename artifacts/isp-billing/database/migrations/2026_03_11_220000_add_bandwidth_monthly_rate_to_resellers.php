<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->decimal('bandwidth_monthly_rate', 10, 2)->default(0)->after('facebook_bandwidth')
                  ->comment('Monthly billing rate for bandwidth resellers (ISP charges this to reseller)');
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn('bandwidth_monthly_rate');
        });
    }
};
