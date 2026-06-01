<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_categories')) {
            Schema::table('product_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('product_categories', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_categories') && Schema::hasColumn('product_categories', 'deleted_at')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
