<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dropdown_options')) {
            Schema::create('dropdown_options', function (Blueprint $table) {
                $table->id();
                $table->string('group_name', 100);
                $table->string('label', 255);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dropdown_options');
    }
};
