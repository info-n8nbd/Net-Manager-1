<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->string('status')->default('running');
            $table->string('mode')->default('dry_run');
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('router_user_count')->default(0);
            $table->integer('billing_user_count')->default(0);
            $table->integer('synced_count')->default(0);
            $table->integer('only_on_router_count')->default(0);
            $table->integer('only_in_billing_count')->default(0);
            $table->integer('mismatch_count')->default(0);
            $table->integer('actions_applied')->default(0);
            $table->integer('actions_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('mikrotik_devices')->onDelete('cascade');
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['device_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('mikrotik_sync_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_run_id');
            $table->string('username');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('diff_type');
            $table->string('router_profile')->nullable();
            $table->string('billing_profile')->nullable();
            $table->string('router_service')->nullable();
            $table->boolean('router_disabled')->default(false);
            $table->string('billing_status')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_code')->nullable();
            $table->string('proposed_action')->nullable();
            $table->string('action_status')->default('pending');
            $table->text('action_message')->nullable();
            $table->timestamp('action_applied_at')->nullable();
            $table->timestamps();

            $table->foreign('sync_run_id')->references('id')->on('mikrotik_sync_runs')->onDelete('cascade');
            $table->index(['sync_run_id', 'diff_type']);
            $table->index('username');
            $table->index('action_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_sync_items');
        Schema::dropIfExists('mikrotik_sync_runs');
    }
};
