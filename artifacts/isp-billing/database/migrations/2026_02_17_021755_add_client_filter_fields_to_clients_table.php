<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'client_type')) {
                $table->string('client_type', 50)->nullable()->default('regular');
            }
            if (!Schema::hasColumn('clients', 'custom_status')) {
                $table->string('custom_status', 100)->nullable();
            }
            if (!Schema::hasColumn('clients', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable();
            }
            if (!Schema::hasColumn('clients', 'effective_to')) {
                $table->date('effective_to')->nullable();
            }
            if (!Schema::hasColumn('clients', 'non_effective_from')) {
                $table->date('non_effective_from')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = ['client_type', 'custom_status', 'assigned_to', 'effective_to', 'non_effective_from'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
