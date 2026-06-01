<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->string('nid_front_image')->nullable()->change();
            $table->string('nid_back_image')->nullable()->change();
            $table->string('full_name_bangla')->nullable()->change();
            
            $table->decimal('signup_fee', 10, 2)->default(0)->after('installation_charge');
            $table->string('payment_method')->nullable()->after('signup_fee');
            $table->date('next_billing_date')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('new_connection_applications', function (Blueprint $table) {
            $table->dropColumn(['signup_fee', 'payment_method', 'next_billing_date']);
        });
    }
};
