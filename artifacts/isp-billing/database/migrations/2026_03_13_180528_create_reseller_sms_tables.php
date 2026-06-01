<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_sms_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('provider')->default('custom');
            $table->string('api_key')->nullable();
            $table->string('sender_id')->nullable();
            $table->string('api_url')->nullable();
            $table->json('custom_api_config')->nullable();
            $table->json('event_settings')->nullable();
            $table->json('templates')->nullable();
            $table->timestamps();

            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
        });

        Schema::create('reseller_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('phone', 20);
            $table->text('message');
            $table->string('event_type', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('response')->nullable();
            $table->string('skip_reason')->nullable();
            $table->timestamps();

            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
            $table->index(['reseller_id', 'created_at']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_sms_logs');
        Schema::dropIfExists('reseller_sms_settings');
    }
};
