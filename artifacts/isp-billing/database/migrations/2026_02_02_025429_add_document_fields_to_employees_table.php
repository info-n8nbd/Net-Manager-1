<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'nid_photo_front')) {
                $table->string('nid_photo_front')->nullable()->after('nid');
            }
            if (!Schema::hasColumn('employees', 'nid_photo_back')) {
                $table->string('nid_photo_back')->nullable()->after('nid_photo_front');
            }
            if (!Schema::hasColumn('employees', 'cv_file')) {
                $table->string('cv_file')->nullable()->after('photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['nid_photo_front', 'nid_photo_back', 'cv_file']);
        });
    }
};
