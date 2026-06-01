<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->date('temp_deadline_date')->nullable()->after('payment_deadline');
            $table->string('temp_deadline_month', 7)->nullable()->after('temp_deadline_date');
            $table->text('temp_deadline_reason')->nullable()->after('temp_deadline_month');
            $table->unsignedBigInteger('temp_deadline_applied_by')->nullable()->after('temp_deadline_reason');
            $table->timestamp('temp_deadline_applied_at')->nullable()->after('temp_deadline_applied_by');
            $table->boolean('sms_opt_out')->default(false)->after('temp_deadline_applied_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'temp_deadline_date',
                'temp_deadline_month',
                'temp_deadline_reason',
                'temp_deadline_applied_by',
                'temp_deadline_applied_at',
                'sms_opt_out',
            ]);
        });
    }
};
