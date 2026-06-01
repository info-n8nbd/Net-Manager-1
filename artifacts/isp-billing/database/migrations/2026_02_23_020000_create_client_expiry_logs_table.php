<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_expiry_logs')) {
            Schema::create('client_expiry_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('action');
                $table->string('previous_status')->nullable();
                $table->string('new_status')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('expire_date')->nullable();
                $table->boolean('mikrotik_success')->nullable();
                $table->boolean('profile_shifted')->nullable();
                $table->timestamps();

                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
                $table->index('client_id');
                $table->index('action');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_expiry_logs');
    }
};
