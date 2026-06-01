<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('client_id')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('alt_phone')->nullable();
            $table->text('address');
            $table->string('nid')->nullable();
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->string('username')->unique();
            $table->string('pppoe_password')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('ip_address')->nullable();
            $table->date('connection_date')->nullable();
            $table->date('expire_date')->nullable();
            $table->integer('billing_date')->default(1);
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->enum('connection_status', ['online', 'offline'])->default('offline');
            $table->unsignedBigInteger('reseller_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'zone_id']);
            $table->index('client_id');
        });

        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('document_type');
            $table->string('document_name');
            $table->string('file_path');
            $table->timestamps();
        });

        Schema::create('client_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        Schema::create('client_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('old_status');
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_status_logs');
        Schema::dropIfExists('client_packages');
        Schema::dropIfExists('client_documents');
        Schema::dropIfExists('clients');
    }
};
