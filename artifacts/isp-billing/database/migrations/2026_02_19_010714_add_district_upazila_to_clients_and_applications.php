<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('box_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->after('district_id')->constrained('upazilas')->nullOnDelete();
        });

        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('zone_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->after('district_id')->constrained('upazilas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['upazila_id']);
            $table->dropColumn(['district_id', 'upazila_id']);
        });

        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['upazila_id']);
            $table->dropColumn(['district_id', 'upazila_id']);
        });
    }
};
