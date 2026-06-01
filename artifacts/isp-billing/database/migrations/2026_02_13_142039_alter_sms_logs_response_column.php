<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->text('response')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->string('response', 255)->nullable()->change();
        });
    }
};
