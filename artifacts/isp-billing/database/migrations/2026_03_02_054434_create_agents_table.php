<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id')->unique();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->decimal('default_commission', 5, 2)->default(0);
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->enum('marital_status', ['unmarried', 'married'])->default('unmarried');
            $table->string('blood_group')->nullable();
            $table->string('national_id')->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('personal_contact')->nullable();
            $table->string('whatsapp_no')->nullable();
            $table->string('office_contact')->nullable();
            $table->string('family_contact')->nullable();
            $table->string('first_reference_no')->nullable();
            $table->string('second_reference_no')->nullable();
            $table->string('email')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_pppoe_id')->nullable();
            $table->string('zone_address')->nullable();
            $table->string('purpose')->nullable();
            $table->text('note')->nullable();
            $table->date('date')->nullable();
            $table->decimal('payment_amount', 12, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commissions');
        Schema::dropIfExists('agents');
    }
};
