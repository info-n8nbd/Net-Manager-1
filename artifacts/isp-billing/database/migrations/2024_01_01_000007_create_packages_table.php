<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('price', 12, 2);
            $table->string('bandwidth');
            $table->integer('download_speed');
            $table->integer('upload_speed');
            $table->string('speed_unit')->default('Mbps');
            $table->enum('connection_type', ['PPPoE', 'Static IP', 'Hotspot', 'DHCP'])->default('PPPoE');
            $table->integer('validity_days')->default(30);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_public')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('package_speeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->string('time_from');
            $table->string('time_to');
            $table->integer('download_speed');
            $table->integer('upload_speed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_speeds');
        Schema::dropIfExists('packages');
    }
};
