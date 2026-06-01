<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('android_sms_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('locked_by')->nullable()->after('company_id');
            $table->timestamp('locked_at')->nullable()->after('locked_by');
            $table->foreign('locked_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('android_sms_logs', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropColumn(['locked_by', 'locked_at']);
        });
    }
};
