<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('email_settings')) {
            Schema::create('email_settings', function (Blueprint $table) {
                $table->id();
                $table->string('mailer')->default('smtp');
                $table->string('host')->default('smtp.gmail.com');
                $table->integer('port')->default(587);
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->string('encryption')->default('tls');
                $table->string('from_address')->default('noreply@isp.com');
                $table->string('from_name')->default('SOFTYNEX');
                $table->boolean('is_active')->default(false);
                $table->boolean('auto_bill_reminder')->default(false);
                $table->boolean('auto_expiry_warning')->default(false);
                $table->boolean('auto_payment_confirmation')->default(true);
                $table->integer('bill_reminder_days_before')->default(3);
                $table->integer('expiry_warning_days_before')->default(3);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->string('to_email');
                $table->string('to_name')->nullable();
                $table->string('subject');
                $table->string('template')->nullable();
                $table->enum('status', ['sent', 'failed', 'queued'])->default('queued');
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_settings');
    }
};
