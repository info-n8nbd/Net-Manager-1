<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('type', 20);
            $table->string('label');
            $table->string('parent_key', 100)->nullable();
            $table->string('entity', 50)->nullable();
            $table->string('action', 20)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('parent_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_permissions');
    }
};
