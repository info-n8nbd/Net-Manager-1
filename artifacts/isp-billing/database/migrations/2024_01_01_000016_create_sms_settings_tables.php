<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('response')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('alt_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('website')->nullable();
            $table->string('currency_symbol')->default('৳');
            $table->string('currency_code')->default('BDT');
            $table->string('btrc_license')->nullable();
            $table->string('bin_no')->nullable();
            $table->string('trade_license')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->timestamps();
        });

        Schema::create('report_logs', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->json('parameters')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_logs');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_templates');
    }
};
