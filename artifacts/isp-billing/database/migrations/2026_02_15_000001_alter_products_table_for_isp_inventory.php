<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            if (!Schema::hasColumn('products', 'type')) {
                $table->string('type')->default('instrument'); // instrument / fiber / accessory
            }

            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand')->nullable();
            }

            if (!Schema::hasColumn('products', 'model')) {
                $table->string('model')->nullable();
            }

            if (!Schema::hasColumn('products', 'barcode_prefix')) {
                $table->string('barcode_prefix')->nullable();
            }

            if (!Schema::hasColumn('products', 'alert_quantity')) {
                $table->integer('alert_quantity')->default(0);
            }
        });

        // Fix product_categories soft delete issue
        Schema::table('product_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('product_categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        // No rollback to avoid breaking existing data
    }
};
