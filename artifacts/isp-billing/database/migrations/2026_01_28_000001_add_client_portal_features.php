<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Client authentication fields
        if (!Schema::hasColumn('clients', 'portal_password')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('portal_password')->nullable()->after('password');
                $table->rememberToken()->after('portal_password');
                $table->timestamp('portal_last_login')->nullable();
            });
        }

        // Client password reset tokens
        if (!Schema::hasTable('client_password_reset_tokens')) {
            Schema::create('client_password_reset_tokens', function (Blueprint $table) {
                $table->string('phone')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Email templates
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('subject');
                $table->text('body');
                $table->json('variables')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Export jobs table
        if (!Schema::hasTable('export_jobs')) {
            Schema::create('export_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->string('format')->default('xlsx');
                $table->json('filters')->nullable();
                $table->string('status')->default('pending');
                $table->string('file_path')->nullable();
                $table->integer('total_records')->default(0);
                $table->integer('processed_records')->default(0);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        // Speed test logs
        if (!Schema::hasTable('speed_test_logs')) {
            Schema::create('speed_test_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->decimal('download_speed', 10, 2)->nullable();
                $table->decimal('upload_speed', 10, 2)->nullable();
                $table->integer('ping')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            });
        }

        // Multi-router sync status
        if (!Schema::hasColumn('mikrotik_devices', 'last_sync_at')) {
            Schema::table('mikrotik_devices', function (Blueprint $table) {
                $table->timestamp('last_sync_at')->nullable();
                $table->json('last_sync_data')->nullable();
                $table->integer('active_users')->default(0);
                $table->integer('total_bandwidth_up')->default(0);
                $table->integer('total_bandwidth_down')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('speed_test_logs');
        Schema::dropIfExists('export_jobs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('client_password_reset_tokens');
        
        if (Schema::hasColumn('clients', 'portal_password')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn(['portal_password', 'remember_token', 'portal_last_login']);
            });
        }
        
        if (Schema::hasColumn('mikrotik_devices', 'last_sync_at')) {
            Schema::table('mikrotik_devices', function (Blueprint $table) {
                $table->dropColumn(['last_sync_at', 'last_sync_data', 'active_users', 'total_bandwidth_up', 'total_bandwidth_down']);
            });
        }
    }
};
