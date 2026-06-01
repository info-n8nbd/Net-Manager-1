<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('new_connection_applications', 'expire_date')) {
                $table->date('expire_date')->nullable()->after('billing_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->dropColumn('expire_date');
        });
    }
};
