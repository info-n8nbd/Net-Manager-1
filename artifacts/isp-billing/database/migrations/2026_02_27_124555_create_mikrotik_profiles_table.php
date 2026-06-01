<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rate_limit')->nullable();
            $table->integer('download_speed')->default(0);
            $table->integer('upload_speed')->default(0);
            $table->string('speed_unit', 10)->default('Mbps');
            $table->string('local_address')->nullable();
            $table->string('remote_address')->nullable();
            $table->string('source_device')->nullable();
            $table->string('profile_type')->default('ppp');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->unique(['name', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_profiles');
    }
};
