<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiber_stocks', function (Blueprint $table) {
            $table->string('purchaser')->nullable()->after('vendor_id');
            $table->string('invoice_number')->nullable()->after('purchaser');
            $table->decimal('per_meter_price', 10, 2)->nullable()->after('remaining_meter');
            $table->decimal('total_cost', 12, 2)->nullable()->after('per_meter_price');
        });
    }

    public function down(): void
    {
        Schema::table('fiber_stocks', function (Blueprint $table) {
            $table->dropColumn(['purchaser', 'invoice_number', 'per_meter_price', 'total_cost']);
        });
    }
};
