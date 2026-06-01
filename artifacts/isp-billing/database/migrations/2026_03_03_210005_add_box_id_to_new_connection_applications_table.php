<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->foreignId('box_id')->nullable()->after('sub_zone_id')->constrained('boxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->dropForeign(['box_id']);
            $table->dropColumn('box_id');
        });
    }
};
