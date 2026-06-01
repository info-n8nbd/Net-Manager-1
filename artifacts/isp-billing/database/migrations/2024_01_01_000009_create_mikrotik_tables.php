<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->integer('port')->default(8728);
            $table->string('username');
            $table->string('password');
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mikrotik_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->string('mikrotik_id')->nullable();
            $table->string('username');
            $table->string('password');
            $table->string('profile')->nullable();
            $table->string('service')->default('pppoe');
            $table->boolean('is_disabled')->default(false);
            $table->timestamps();
        });

        Schema::create('bandwidth_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('target');
            $table->string('max_limit');
            $table->string('burst_limit')->nullable();
            $table->string('burst_threshold')->nullable();
            $table->integer('burst_time')->nullable();
            $table->integer('priority')->default(8);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mac_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('mikrotik_device_id')->constrained()->onDelete('cascade');
            $table->string('mac_address');
            $table->string('ip_address');
            $table->text('comment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mac_bindings');
        Schema::dropIfExists('bandwidth_queues');
        Schema::dropIfExists('mikrotik_users');
        Schema::dropIfExists('mikrotik_devices');
    }
};
