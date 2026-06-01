<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_vendors')) {
            Schema::create('inventory_vendors', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('name');
                $table->index('phone');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_vendors');
    }
};
