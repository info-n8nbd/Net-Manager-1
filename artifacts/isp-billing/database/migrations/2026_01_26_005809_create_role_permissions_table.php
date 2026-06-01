<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('permission_key', 100);
            $table->boolean('is_granted')->default(false);
            
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('granted_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('revoked_at')->nullable();
            $table->text('reason')->nullable();
            
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_key']);
            $table->index(['role_id', 'is_granted']);
            $table->index('permission_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
