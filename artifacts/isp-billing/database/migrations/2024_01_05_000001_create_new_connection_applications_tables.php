<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_connection_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            
            $table->string('full_name_bangla');
            $table->string('full_name_english');
            $table->string('father_husband_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->string('mobile_number');
            $table->string('alternative_mobile')->nullable();
            $table->string('email')->nullable();
            
            $table->string('nid_number');
            $table->string('nid_front_image');
            $table->string('nid_back_image');
            $table->enum('nid_verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            
            $table->string('division')->nullable();
            $table->string('district')->nullable();
            $table->string('thana_upazila')->nullable();
            $table->string('union_ward')->nullable();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->unsignedBigInteger('sub_zone_id')->nullable();
            $table->text('house_road_floor')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('nearby_landmark')->nullable();
            
            $table->enum('connection_type', ['home', 'corporate'])->default('home');
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('bandwidth_speed')->nullable();
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'half_yearly', 'yearly', 'custom'])->default('monthly');
            $table->decimal('installation_charge', 10, 2)->default(0);
            $table->boolean('onu_required')->default(false);
            $table->boolean('router_required')->default(false);
            $table->integer('cable_length_meters')->nullable();
            $table->enum('ip_type', ['dynamic', 'static'])->default('dynamic');
            
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->string('technician_assigned_area')->nullable();
            $table->text('technician_note')->nullable();
            $table->boolean('installation_feasibility')->default(true);
            
            $table->string('house_owner_permission')->nullable();
            $table->string('installation_place_photo')->nullable();
            $table->string('customer_signature')->nullable();
            
            $table->enum('status', ['draft', 'submitted', 'under_review', 'need_correction', 'approved', 'rejected'])->default('draft');
            $table->text('admin_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('correction_request')->nullable();
            
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('created_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('mikrotik_username')->nullable();
            $table->foreignId('mikrotik_device_id')->nullable()->constrained('mikrotik_devices')->nullOnDelete();
            $table->boolean('mikrotik_created')->default(false);
            $table->text('mikrotik_creation_log')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'technician_id']);
            $table->index(['nid_number']);
            $table->index(['mobile_number']);
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('new_connection_applications')->cascadeOnDelete();
            $table->string('document_type');
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('application_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('new_connection_applications')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('nid_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('new_connection_applications')->cascadeOnDelete();
            $table->string('nid_number');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('verification_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->json('verification_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nid_verifications');
        Schema::dropIfExists('application_status_logs');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('new_connection_applications');
    }
};
