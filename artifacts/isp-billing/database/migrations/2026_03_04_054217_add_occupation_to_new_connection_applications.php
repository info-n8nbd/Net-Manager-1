<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('new_connection_applications', 'occupation')) {
                $table->string('occupation', 100)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('new_connection_applications', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable();
            }
            if (!Schema::hasColumn('new_connection_applications', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->dropColumn(['occupation', 'agent_id', 'commission_percent']);
        });
    }
};
