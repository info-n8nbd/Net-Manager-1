<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('network_event_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('network_event_logs', 'source')) {
                $table->string('source')->nullable()->after('event_type');
            }
            if (!Schema::hasColumn('network_event_logs', 'message')) {
                $table->text('message')->nullable()->after('source');
            }
            if (!Schema::hasColumn('network_event_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('network_event_logs', function (Blueprint $table) {
            $table->dropColumnIfExists('source');
            $table->dropColumnIfExists('message');
            $table->dropColumnIfExists('metadata');
        });
    }
};
