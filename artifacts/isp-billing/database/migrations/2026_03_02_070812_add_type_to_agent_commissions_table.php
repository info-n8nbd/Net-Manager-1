<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_commissions', function (Blueprint $table) {
            $table->string('type', 20)->default('commission')->after('agent_id');
            $table->unsignedBigInteger('payment_id')->nullable()->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('agent_commissions', function (Blueprint $table) {
            $table->dropColumn(['type', 'payment_id']);
        });
    }
};
