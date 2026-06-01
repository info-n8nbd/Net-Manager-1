<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->after('reseller_id');
                $table->foreign('agent_id')->references('id')->on('agents')->nullOnDelete();
            }
            if (!Schema::hasColumn('clients', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->nullable()->after('agent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'commission_percent')) {
                $table->dropColumn('commission_percent');
            }
            if (Schema::hasColumn('clients', 'agent_id')) {
                $table->dropForeign(['agent_id']);
                $table->dropColumn('agent_id');
            }
        });
    }
};
