<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id');
            $table->decimal('amount', 12, 2);
            $table->date('collection_date');
            $table->string('payment_method', 50)->default('Cash');
            $table->string('receipt_no', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('company_id')->default(1);
            $table->timestamps();
            $table->index(['user_id', 'collection_date']);
            $table->index(['client_id']);
            $table->index(['status']);
            $table->index(['collection_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_collections');
    }
};
