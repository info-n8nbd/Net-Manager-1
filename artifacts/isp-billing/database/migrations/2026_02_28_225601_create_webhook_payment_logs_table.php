<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 30);
            $table->string('trx_id')->nullable();
            $table->string('sender_number', 20)->nullable();
            $table->string('reference', 100)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->enum('status', ['received', 'matched', 'applied', 'failed', 'duplicate'])->default('received');
            $table->text('failure_reason')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['gateway', 'trx_id']);
            $table->index(['reference']);
            $table->index(['status']);
            $table->index(['client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_payment_logs');
    }
};
