<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix fiber_stocks: add purchase_date alias, notes, status columns
        Schema::table('fiber_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('fiber_stocks', 'notes')) {
                $table->text('notes')->nullable()->after('remaining_meter');
            }
            if (!Schema::hasColumn('fiber_stocks', 'status')) {
                $table->string('status')->default('active')->after('notes');
            }
            if (!Schema::hasColumn('fiber_stocks', 'fiber_type')) {
                $table->string('fiber_type')->default('Single Mode')->after('fiber_code');
            }
        });

        // Normalize product_serials status values: unify 'in_stock' to 'stock'
        \DB::table('product_serials')->where('status', 'in_stock')->update(['status' => 'stock']);

        // Ensure product_serials has barcode column
        Schema::table('product_serials', function (Blueprint $table) {
            if (!Schema::hasColumn('product_serials', 'barcode')) {
                $table->string('barcode')->nullable()->after('serial_number');
            }
        });

        // Add fiber_type to fiber_usages if missing
        Schema::table('fiber_usages', function (Blueprint $table) {
            if (!Schema::hasColumn('fiber_usages', 'fiber_stock_id')) {
                $table->unsignedBigInteger('fiber_stock_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fiber_stocks', function (Blueprint $table) {
            $table->dropColumn(['notes', 'status', 'fiber_type']);
        });
    }
};
