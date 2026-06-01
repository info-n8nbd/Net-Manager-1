<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_fraud_alerts')) {
            Schema::create('payment_fraud_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained()->onDelete('cascade');
                $table->enum('alert_type', ['duplicate_trxid', 'suspicious_amount', 'unusual_pattern']);
                $table->string('transaction_id')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('message');
                $table->boolean('is_resolved')->default(false);
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
                $table->index(['payment_id', 'alert_type']);
            });
        }

        if (!Schema::hasTable('device_warranties')) {
            Schema::create('device_warranties', function (Blueprint $table) {
                $table->id();
                $table->string('device_type');
                $table->unsignedBigInteger('device_id');
                $table->string('serial_number')->nullable();
                $table->date('purchase_date');
                $table->date('warranty_start');
                $table->date('warranty_end');
                $table->string('vendor')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('alert_sent')->default(false);
                $table->timestamps();
                $table->index(['device_type', 'device_id']);
                $table->index('warranty_end');
            });
        }

        if (!Schema::hasTable('client_agreements')) {
            Schema::create('client_agreements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->text('agreement_content');
                $table->string('otp_code')->nullable();
                $table->boolean('otp_verified')->default(false);
                $table->string('signature_image')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('device_info')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('family_groups')) {
            Schema::create('family_groups', function (Blueprint $table) {
                $table->id();
                $table->string('group_name');
                $table->foreignId('primary_client_id')->constrained('clients')->onDelete('cascade');
                $table->decimal('combined_discount', 12, 2)->default(0);
                $table->boolean('combined_billing')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('family_group_members')) {
            Schema::create('family_group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('family_group_id')->constrained()->onDelete('cascade');
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->string('relationship')->nullable();
                $table->timestamps();
                $table->unique(['family_group_id', 'client_id']);
            });
        }

        if (!Schema::hasTable('client_timeline')) {
            Schema::create('client_timeline', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->enum('event_type', ['bill', 'payment', 'ticket', 'status_change', 'package_change', 'line_shift', 'reconnection', 'note']);
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->index(['client_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('area_outage_logs')) {
            Schema::create('area_outage_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('zone_id')->constrained()->onDelete('cascade');
                $table->integer('affected_clients');
                $table->string('outage_type');
                $table->text('description')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('maintenance_modes')) {
            Schema::create('maintenance_modes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('zone_id')->nullable()->constrained()->onDelete('cascade');
                $table->boolean('is_global')->default(false);
                $table->string('reason');
                $table->boolean('pause_alerts')->default(true);
                $table->boolean('pause_auto_disconnect')->default(true);
                $table->timestamp('started_at');
                $table->timestamp('scheduled_end')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('clients', 'last_online')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->timestamp('last_online')->nullable()->after('connection_status');
            });
        }

        if (!Schema::hasColumn('clients', 'family_group_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('family_group_id')->nullable()->after('reseller_id');
            });
        }

        if (!Schema::hasColumn('tickets', 'auto_created')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->boolean('auto_created')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_modes');
        Schema::dropIfExists('area_outage_logs');
        Schema::dropIfExists('client_timeline');
        Schema::dropIfExists('family_group_members');
        Schema::dropIfExists('family_groups');
        Schema::dropIfExists('client_agreements');
        Schema::dropIfExists('device_warranties');
        Schema::dropIfExists('payment_fraud_alerts');
    }
};
