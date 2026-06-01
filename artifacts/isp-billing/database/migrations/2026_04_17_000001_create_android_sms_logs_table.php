<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('android_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->text('raw_sms');
            $table->string('gateway', 20)->default('unknown')->comment('bkash,nagad,rocket,unknown');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('sender_number', 20)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('trx_id', 100)->nullable()->unique();
            $table->json('parsed_data')->nullable();
            $table->enum('status', ['pending','matched','applied','unknown','duplicate','failed'])->default('pending');
            $table->string('failure_reason')->nullable();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->string('device_id', 50)->nullable()->comment('Identifier of the Android device');
            $table->string('device_ip', 50)->nullable();
            $table->string('api_token', 100)->nullable();
            $table->timestamp('sms_received_at')->nullable()->comment('Time SMS was received on Android');
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'company_id']);
            $table->index(['gateway', 'created_at']);
            $table->index('sender_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('android_sms_logs');
    }
};
