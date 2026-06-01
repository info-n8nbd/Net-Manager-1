<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_client_recharges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id');
            $table->unsignedBigInteger('client_id');
            $table->integer('bill_month');
            $table->integer('bill_year');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('package_name')->nullable();
            $table->decimal('package_price', 10, 2)->default(0);
            $table->decimal('recharge_amount', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days')->default(30);
            $table->string('status')->default('active');
            $table->unsignedBigInteger('recharged_by')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
            $table->foreign('recharged_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['reseller_id', 'bill_month', 'bill_year']);
            $table->index(['client_id', 'bill_month', 'bill_year']);
        });

        Schema::create('reseller_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id');
            $table->unsignedBigInteger('reseller_bill_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('payment_method')->default('cash');
            $table->string('payment_reference')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
            $table->foreign('reseller_bill_id')->references('id')->on('reseller_bills')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['reseller_id', 'payment_date']);
        });

        if (!Schema::hasColumn('resellers', 'over_due_limit')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->decimal('over_due_limit', 10, 2)->default(0)->after('credit_limit');
            });
        }
        if (!Schema::hasColumn('resellers', 'minimum_recharge_days')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->integer('minimum_recharge_days')->default(30)->after('over_due_limit');
            });
        }
        if (!Schema::hasColumn('resellers', 'thana')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->string('thana')->nullable()->after('address');
            });
        }

        if (!Schema::hasColumn('reseller_bills', 'total_clients')) {
            Schema::table('reseller_bills', function (Blueprint $table) {
                $table->integer('total_clients')->default(0)->after('bill_year');
                $table->integer('active_clients')->default(0)->after('total_clients');
            });
        }
        if (!Schema::hasColumn('reseller_bills', 'commission_rate')) {
            Schema::table('reseller_bills', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)->default(0)->after('due_amount');
                $table->decimal('commission_amount', 10, 2)->default(0)->after('commission_rate');
                $table->decimal('discount_amount', 10, 2)->default(0)->after('commission_amount');
            });
        }
        if (!Schema::hasColumn('reseller_bills', 'opening_balance')) {
            Schema::table('reseller_bills', function (Blueprint $table) {
                $table->decimal('opening_balance', 10, 2)->default(0)->after('discount_amount');
                $table->decimal('closing_balance', 10, 2)->default(0)->after('opening_balance');
            });
        }
        if (!Schema::hasColumn('reseller_bills', 'company_id')) {
            Schema::table('reseller_bills', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('closing_balance');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_bill_payments');
        Schema::dropIfExists('reseller_client_recharges');

        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn(['over_due_limit', 'minimum_recharge_days', 'thana']);
        });
        Schema::table('reseller_bills', function (Blueprint $table) {
            $table->dropColumn(['total_clients', 'active_clients', 'commission_rate', 'commission_amount', 'discount_amount', 'opening_balance', 'closing_balance', 'company_id']);
        });
    }
};
