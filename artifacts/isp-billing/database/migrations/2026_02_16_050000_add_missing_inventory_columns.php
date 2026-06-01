<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix fiber_stocks: add missing columns
        Schema::table('fiber_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('fiber_stocks', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('fiber_stocks', 'status')) {
                $table->string('status')->default('active')->after('remaining_meter');
            }
            if (!Schema::hasColumn('fiber_stocks', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('fiber_code');
            }
            if (!Schema::hasColumn('fiber_stocks', 'fiber_type')) {
                $table->string('fiber_type')->nullable()->after('brand_name');
            }
            if (!Schema::hasColumn('fiber_stocks', 'notes')) {
                $table->text('notes')->nullable()->after('stock_date');
            }
        });

        // Fix product_categories: add deleted_at for SoftDeletes
        Schema::table('product_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('product_categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Fix products: add missing columns
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'type')) {
                $table->string('type')->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand')->nullable()->after('unit');
            }
            if (!Schema::hasColumn('products', 'model')) {
                $table->string('model')->nullable()->after('brand');
            }
            if (!Schema::hasColumn('products', 'barcode_prefix')) {
                $table->string('barcode_prefix')->nullable()->after('warranty_months');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fiber_stocks', function (Blueprint $table) {
            $cols = array_filter(['name', 'status', 'brand_name', 'fiber_type', 'notes'], fn($c) => Schema::hasColumn('fiber_stocks', $c));
            if (!empty($cols)) $table->dropColumn($cols);
        });
        Schema::table('product_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_categories', 'deleted_at')) $table->dropSoftDeletes();
        });
        Schema::table('products', function (Blueprint $table) {
            $cols = array_filter(['type', 'brand', 'model', 'barcode_prefix'], fn($c) => Schema::hasColumn('products', $c));
            if (!empty($cols)) $table->dropColumn($cols);
        });
    }
};
