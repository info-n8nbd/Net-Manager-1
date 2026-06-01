<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_logs', 'event_type')) {
                $table->string('event_type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('sms_logs', 'recipient_type')) {
                $table->string('recipient_type')->default('client')->after('event_type');
            }
            if (!Schema::hasColumn('sms_logs', 'skip_reason')) {
                $table->string('skip_reason')->nullable()->after('response');
            }
        });
        
        if (!Schema::hasColumn('sms_logs', 'status')) {
            Schema::table('sms_logs', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn(['event_type', 'recipient_type', 'skip_reason']);
        });
    }
};
