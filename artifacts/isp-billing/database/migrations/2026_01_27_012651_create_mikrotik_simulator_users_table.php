<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_simulator_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password')->default('secret123');
            $table->string('profile')->default('default');
            $table->string('service')->default('pppoe');
            $table->boolean('disabled')->default(false);
            $table->string('comment')->nullable();
            $table->string('caller_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->bigInteger('bytes_in')->default(0);
            $table->bigInteger('bytes_out')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_simulator_users');
    }
};
