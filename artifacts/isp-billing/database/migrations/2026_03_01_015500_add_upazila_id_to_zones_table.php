<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->foreignId('upazila_id')->nullable()->after('name')->constrained('upazilas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeign(['upazila_id']);
            $table->dropColumn('upazila_id');
        });
    }
};
