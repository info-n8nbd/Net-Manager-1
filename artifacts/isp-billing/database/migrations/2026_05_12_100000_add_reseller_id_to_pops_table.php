<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pops', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_id')->nullable()->after('id');
            $table->string('pop_source', 30)->default('standalone')->after('reseller_id');
            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pops', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->dropColumn(['reseller_id', 'pop_source']);
        });
    }
};
