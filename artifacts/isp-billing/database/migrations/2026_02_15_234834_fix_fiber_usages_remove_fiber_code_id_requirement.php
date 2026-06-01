<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiber_usages', function (Blueprint $table) {
            $table->dropForeign(['fiber_code_id']);
            $table->bigInteger('fiber_code_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fiber_usages', function (Blueprint $table) {
            $table->bigInteger('fiber_code_id')->nullable(false)->change();
            $table->foreign('fiber_code_id')->references('id')->on('fiber_codes')->onDelete('cascade');
        });
    }
};
