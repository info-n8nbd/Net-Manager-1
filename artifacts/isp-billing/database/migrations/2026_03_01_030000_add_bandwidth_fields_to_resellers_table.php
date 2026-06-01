<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            if (!Schema::hasColumn('resellers', 'raw_bandwidth')) {
                $table->string('raw_bandwidth')->nullable()->after('type');
            }
            if (!Schema::hasColumn('resellers', 'youtube_bandwidth')) {
                $table->string('youtube_bandwidth')->nullable()->after('raw_bandwidth');
            }
            if (!Schema::hasColumn('resellers', 'facebook_bandwidth')) {
                $table->string('facebook_bandwidth')->nullable()->after('youtube_bandwidth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn(['raw_bandwidth', 'youtube_bandwidth', 'facebook_bandwidth']);
        });
    }
};
