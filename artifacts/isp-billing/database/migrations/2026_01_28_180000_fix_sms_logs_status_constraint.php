<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old constraint if exists
        DB::statement('ALTER TABLE sms_logs DROP CONSTRAINT IF EXISTS sms_logs_status_check');
        
        // Add new constraint with 'skipped' status included
        DB::statement("ALTER TABLE sms_logs ADD CONSTRAINT sms_logs_status_check CHECK (status IN ('pending', 'sent', 'failed', 'skipped'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sms_logs DROP CONSTRAINT IF EXISTS sms_logs_status_check');
        DB::statement("ALTER TABLE sms_logs ADD CONSTRAINT sms_logs_status_check CHECK (status IN ('pending', 'sent', 'failed'))");
    }
};
