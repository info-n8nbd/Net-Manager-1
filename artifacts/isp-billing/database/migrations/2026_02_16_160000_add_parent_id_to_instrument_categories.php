<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instrument_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('instrument_categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                $table->foreign('parent_id')->references('id')->on('instrument_categories')->onDelete('cascade');
            }
            if (!Schema::hasColumn('instrument_categories', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instrument_categories', function (Blueprint $table) {
            if (Schema::hasColumn('instrument_categories', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('instrument_categories', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
