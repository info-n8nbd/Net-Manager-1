<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sms_settings')) {
            Schema::create('sms_settings', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->default('none');
                $table->string('api_key')->nullable();
                $table->string('api_secret')->nullable();
                $table->string('sender_id')->nullable();
                $table->string('base_url')->nullable();
                $table->boolean('is_active')->default(false);
                $table->json('event_settings')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sms_gateways')) {
            Schema::create('sms_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('api_url')->nullable();
                $table->json('config')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateways');
        Schema::dropIfExists('sms_settings');
    }
};
