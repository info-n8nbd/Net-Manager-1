<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add PBX columns to voice_call_settings
        Schema::table('voice_call_settings', function (Blueprint $table) {
            $table->string('pbx_ws_url')->nullable()->after('from_number');
            $table->string('pbx_extension')->nullable()->after('pbx_ws_url');
            $table->string('pbx_password')->nullable()->after('pbx_extension');
            $table->json('pbx_trunk')->nullable()->after('pbx_password');
        });

        // Fix call_type check constraint to include 'pbx_direct'
        DB::statement("ALTER TABLE voice_call_logs DROP CONSTRAINT IF EXISTS voice_call_logs_call_type_check");
        DB::statement("ALTER TABLE voice_call_logs ADD CONSTRAINT voice_call_logs_call_type_check CHECK (call_type IN ('bill_reminder','expiry_warning','payment_confirmation','custom','announcement','pbx_direct'))");

        // Fix status check constraint to include 'skipped'
        DB::statement("ALTER TABLE voice_call_logs DROP CONSTRAINT IF EXISTS voice_call_logs_status_check");
        DB::statement("ALTER TABLE voice_call_logs ADD CONSTRAINT voice_call_logs_status_check CHECK (status IN ('queued','initiated','ringing','answered','completed','failed','no_answer','busy','skipped'))");
    }

    public function down(): void
    {
        Schema::table('voice_call_settings', function (Blueprint $table) {
            $table->dropColumn(['pbx_ws_url','pbx_extension','pbx_password','pbx_trunk']);
        });
    }
};
