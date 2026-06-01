<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('in_progress');
            $table->integer('current_phase')->default(1);
            $table->string('phase1_status')->default('pending');
            $table->string('phase2_status')->default('pending');
            $table->string('phase3_status')->default('pending');
            $table->string('phase4_status')->default('pending');
            $table->string('phase5_status')->default('pending');
            $table->json('phase1_summary')->nullable();
            $table->json('phase2_summary')->nullable();
            $table->json('phase3_summary')->nullable();
            $table->json('phase4_summary')->nullable();
            $table->json('phase5_summary')->nullable();
            $table->unsignedBigInteger('import_job_id')->nullable();
            $table->unsignedBigInteger('sync_run_id')->nullable();
            $table->integer('total_clients_imported')->default(0);
            $table->integer('total_bills_imported')->default(0);
            $table->integer('total_payments_imported')->default(0);
            $table->integer('total_routers_scanned')->default(0);
            $table->integer('total_router_users_found')->default(0);
            $table->integer('total_reconciled')->default(0);
            $table->integer('total_synced')->default(0);
            $table->integer('total_errors')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('import_job_id')->references('id')->on('import_jobs')->nullOnDelete();
            $table->foreign('initiated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('migration_router_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('migration_run_id');
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('device_host')->nullable();
            $table->string('username');
            $table->string('profile')->default('default');
            $table->string('password')->nullable();
            $table->string('service')->default('pppoe');
            $table->boolean('disabled')->default(false);
            $table->text('comment')->nullable();
            $table->string('remote_address')->nullable();
            $table->timestamp('last_logged_out')->nullable();
            $table->unsignedBigInteger('billing_client_id')->nullable();
            $table->string('match_status')->default('unmatched');
            $table->string('match_type')->nullable();
            $table->timestamps();

            $table->foreign('migration_run_id')->references('id')->on('migration_runs')->cascadeOnDelete();
            $table->foreign('device_id')->references('id')->on('mikrotik_devices')->nullOnDelete();
            $table->foreign('billing_client_id')->references('id')->on('clients')->nullOnDelete();
            $table->index('match_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_router_snapshots');
        Schema::dropIfExists('migration_runs');
    }
};
