<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status')->default('approved')->after('collected_by');
            }
            if (!Schema::hasColumn('payments', 'source')) {
                $table->string('source')->default('admin')->after('status');
            }
            if (!Schema::hasColumn('payments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('source');
            }
            if (!Schema::hasColumn('payments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
        
        Schema::table('bills', function (Blueprint $table) {
            if (!Schema::hasColumn('bills', 'late_fee_applied')) {
                $table->decimal('late_fee_applied', 10, 2)->nullable()->after('due_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['status', 'source', 'approved_by', 'approved_at']);
        });
        
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn('late_fee_applied');
        });
    }
};
