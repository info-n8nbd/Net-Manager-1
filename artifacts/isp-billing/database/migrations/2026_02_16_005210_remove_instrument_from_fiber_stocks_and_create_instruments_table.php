<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('fiber_stocks', 'instrument')) {
            Schema::table('fiber_stocks', function (Blueprint $table) {
                $table->dropColumn('instrument');
            });
        }

        if (!Schema::hasTable('instruments')) {
            Schema::create('instruments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('model')->nullable();
                $table->string('brand')->nullable();
                $table->string('serial_number')->nullable();
                $table->string('category')->nullable();
                $table->decimal('purchase_price', 12, 2)->nullable();
                $table->date('purchase_date')->nullable();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->string('condition')->default('good');
                $table->string('status')->default('available');
                $table->string('assigned_to')->nullable();
                $table->date('warranty_expiry')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');

        Schema::table('fiber_stocks', function (Blueprint $table) {
            $table->string('instrument')->nullable()->after('brand_name');
        });
    }
};
