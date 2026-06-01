<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source_software')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('data_type');
            $table->enum('status', ['pending', 'validating', 'validated', 'importing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('user_id');
            $table->json('field_mapping')->nullable();
            $table->unsignedBigInteger('mapping_template_id')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->json('validation_summary')->nullable();
            $table->json('import_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'created_at']);
            $table->index(['data_type', 'status']);
        });

        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_job_id');
            $table->integer('row_number');
            $table->json('original_data');
            $table->json('mapped_data')->nullable();
            $table->enum('status', ['pending', 'valid', 'invalid', 'duplicate', 'imported', 'failed', 'skipped'])->default('pending');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('validation_errors')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('import_job_id')->references('id')->on('import_jobs')->onDelete('cascade');
            $table->index(['import_job_id', 'status']);
            $table->index(['import_job_id', 'row_number']);
        });

        Schema::create('field_mapping_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('source_software')->nullable();
            $table->string('data_type');
            $table->json('mapping');
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_default')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['data_type', 'is_default']);
        });

        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('data_types');
            $table->string('file_format');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedBigInteger('user_id');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->json('export_options')->nullable();
            $table->json('export_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'created_at']);
        });

        Schema::table('import_jobs', function (Blueprint $table) {
            $table->foreign('mapping_template_id')->references('id')->on('field_mapping_templates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->dropForeign(['mapping_template_id']);
        });
        Schema::dropIfExists('export_jobs');
        Schema::dropIfExists('field_mapping_templates');
        Schema::dropIfExists('import_logs');
        Schema::dropIfExists('import_jobs');
    }
};
