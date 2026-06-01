<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationSettingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('automation_settings', function (Blueprint $table) {
            $table->id();
            $table->time('bill_generate_time')->default('09:00');
            $table->time('disconnect_time')->default('23:59');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_settings');
    }
}
