<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total_collected', 12, 2)->default(0);
            $table->decimal('total_submitted', 12, 2)->default(0);
            $table->decimal('current_due', 12, 2)->default(0);
            $table->date('last_collection_date')->nullable();
            $table->date('last_submission_date')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_no')->unique();
            $table->foreignId('staff_user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->date('submission_date');
            $table->string('payment_method')->default('cash');
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'received', 'rejected'])->default('pending');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reseller_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no')->unique();
            $table->foreignId('reseller_id')->constrained('resellers')->onDelete('cascade');
            $table->integer('bill_month');
            $table->integer('bill_year');
            $table->decimal('total_recharge', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->timestamps();
            $table->unique(['reseller_id', 'bill_month', 'bill_year']);
        });

        if (!Schema::hasColumn('payments', 'submitted_to_admin')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->boolean('submitted_to_admin')->default(false)->after('collected_by');
                $table->timestamp('submitted_at')->nullable()->after('submitted_to_admin');
                $table->foreignId('submission_id')->nullable()->after('submitted_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['submitted_to_admin', 'submitted_at', 'submission_id']);
        });
        Schema::dropIfExists('reseller_bills');
        Schema::dropIfExists('staff_submissions');
        Schema::dropIfExists('staff_dues');
    }
};
