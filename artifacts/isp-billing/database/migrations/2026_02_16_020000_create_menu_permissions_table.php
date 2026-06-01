<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menu_permissions')) {
            Schema::create('menu_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->string('menu_key');
                $table->string('submenu_key')->nullable();
                $table->string('action_key')->nullable();
                $table->boolean('allowed')->default(true);
                $table->timestamps();

                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->unique(['role_id', 'menu_key', 'submenu_key'], 'menu_perm_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_permissions');
    }
};
