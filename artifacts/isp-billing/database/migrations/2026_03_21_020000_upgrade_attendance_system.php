<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'lunch_start')) {
                $table->time('lunch_start')->nullable()->after('check_out');
            }
            if (!Schema::hasColumn('attendances', 'lunch_end')) {
                $table->time('lunch_end')->nullable()->after('lunch_start');
            }
            if (!Schema::hasColumn('attendances', 'overtime_hours')) {
                $table->decimal('overtime_hours', 5, 2)->default(0)->after('lunch_end');
            }
            if (!Schema::hasColumn('attendances', 'late_minutes')) {
                $table->integer('late_minutes')->default(0)->after('overtime_hours');
            }
            if (!Schema::hasColumn('attendances', 'early_leave_minutes')) {
                $table->integer('early_leave_minutes')->default(0)->after('late_minutes');
            }
            if (!Schema::hasColumn('attendances', 'total_work_hours')) {
                $table->decimal('total_work_hours', 5, 2)->default(0)->after('early_leave_minutes');
            }
        });

        if (!Schema::hasColumn('attendances', 'status')) {
            return;
        }

        try {
            DB::statement("ALTER TABLE attendances DROP CONSTRAINT IF EXISTS attendances_status_check");
        } catch (\Exception $e) {}

        try {
            DB::statement("ALTER TABLE attendances ADD CONSTRAINT attendances_status_check CHECK (status IN ('present', 'absent', 'late', 'half_day', 'leave', 'holiday', 'weekend', 'remote'))");
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $columns = ['lunch_start', 'lunch_end', 'overtime_hours', 'late_minutes', 'early_leave_minutes', 'total_work_hours'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('attendances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
