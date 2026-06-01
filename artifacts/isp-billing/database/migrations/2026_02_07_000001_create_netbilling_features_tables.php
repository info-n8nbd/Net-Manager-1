<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend user_type enum to include new panel types
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check");
        DB::statement("ALTER TABLE users ALTER COLUMN user_type TYPE varchar(20)");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_user_type_check CHECK (user_type IN ('admin','employee','client','reseller','technician','sub_reseller','manager','card_distributor'))");

        // 2. Branches table - multi-location ISP support
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_main')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Link zones to branches
        if (!Schema::hasColumn('zones', 'branch_id')) {
            Schema::table('zones', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            });
        }

        // 4. POP (Point of Presence) Management
        Schema::create('pops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->integer('total_bandwidth_mbps')->default(0);
            $table->integer('used_bandwidth_mbps')->default(0);
            $table->string('uplink_provider')->nullable();
            $table->string('uplink_type')->nullable();
            $table->foreignId('mikrotik_device_id')->nullable()->constrained('mikrotik_devices')->onDelete('set null');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Sub-Reseller support - extend resellers table
        if (!Schema::hasColumn('resellers', 'parent_reseller_id')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->foreignId('parent_reseller_id')->nullable()->constrained('resellers')->onDelete('set null');
                $table->enum('reseller_level', ['reseller', 'sub_reseller'])->default('reseller');
                $table->decimal('parent_commission_rate', 5, 2)->default(0);
                $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            });
        }

        // 6. Sub-Reseller commissions tracking
        Schema::create('sub_reseller_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_reseller_id')->constrained('resellers')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('payment_amount', 15, 2);
            $table->decimal('reseller_commission', 15, 2);
            $table->decimal('sub_reseller_commission', 15, 2);
            $table->decimal('admin_commission', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 7. Manager assignments
        Schema::create('manager_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('permissions')->nullable();
            $table->json('allowed_zones')->nullable();
            $table->boolean('can_manage_billing')->default(true);
            $table->boolean('can_manage_clients')->default(true);
            $table->boolean('can_manage_staff')->default(false);
            $table->boolean('can_view_reports')->default(true);
            $table->boolean('can_manage_resellers')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 8. IPAM - IP Pools
        Schema::create('ip_pools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('network_address');
            $table->string('subnet_mask')->nullable();
            $table->integer('cidr_prefix');
            $table->enum('ip_version', ['ipv4', 'ipv6'])->default('ipv4');
            $table->string('gateway')->nullable();
            $table->string('dns_primary')->nullable();
            $table->string('dns_secondary')->nullable();
            $table->enum('pool_type', ['pppoe', 'hotspot', 'static', 'dhcp', 'management'])->default('pppoe');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('pop_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('mikrotik_device_id')->nullable()->constrained('mikrotik_devices')->onDelete('set null');
            $table->integer('total_ips')->default(0);
            $table->integer('used_ips')->default(0);
            $table->integer('reserved_ips')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 9. IPAM - IP Allocations
        Schema::create('ip_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ip_pool_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->enum('status', ['available', 'assigned', 'reserved', 'disabled'])->default('available');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('mac_address')->nullable();
            $table->string('hostname')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['ip_pool_id', 'ip_address']);
            $table->index('ip_address');
            $table->index('status');
        });

        // 10. Minute/Pay-as-you-go packages
        Schema::create('minute_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('duration_minutes');
            $table->decimal('price', 12, 2);
            $table->integer('bandwidth_up_mbps')->nullable();
            $table->integer('bandwidth_down_mbps')->nullable();
            $table->enum('connection_type', ['pppoe', 'hotspot'])->default('hotspot');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 11. Minute sales / usage tracking
        Schema::create('minute_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('minute_package_id')->constrained()->onDelete('cascade');
            $table->integer('total_minutes');
            $table->integer('used_minutes')->default(0);
            $table->integer('remaining_minutes')->default(0);
            $table->decimal('amount_paid', 12, 2);
            $table->timestamp('purchased_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->enum('status', ['active', 'expired', 'exhausted', 'refunded'])->default('active');
            $table->foreignId('sold_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reseller_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        // 12. OTP verification
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('otp_code');
            $table->enum('purpose', ['registration', 'login', 'password_reset', 'payment'])->default('registration');
            $table->boolean('is_verified')->default(false);
            $table->integer('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['phone', 'purpose']);
        });

        // 13. Secure payment links
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('bill_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->enum('status', ['active', 'paid', 'expired', 'cancelled'])->default('active');
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_method')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('sent_via')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamps();

            $table->index('token');
            $table->index('status');
        });

        // 14. Voice call notifications
        Schema::create('voice_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('call_type', ['bill_reminder', 'expiry_warning', 'payment_confirmation', 'custom', 'announcement'])->default('bill_reminder');
            $table->text('message_text')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_call_id')->nullable();
            $table->enum('status', ['queued', 'initiated', 'ringing', 'answered', 'completed', 'failed', 'no_answer', 'busy'])->default('queued');
            $table->integer('duration_seconds')->default(0);
            $table->decimal('cost', 8, 4)->default(0);
            $table->json('provider_response')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'call_type']);
        });

        // 15. Voice call settings
        Schema::create('voice_call_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('twilio');
            $table->json('credentials')->nullable();
            $table->string('from_number')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('auto_bill_reminder')->default(false);
            $table->boolean('auto_expiry_warning')->default(false);
            $table->boolean('auto_payment_confirmation')->default(false);
            $table->integer('reminder_days_before')->default(3);
            $table->timestamps();
        });

        // 16. Daily billing configuration for resellers
        Schema::create('reseller_billing_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->enum('billing_mode', ['monthly', 'daily', 'per_minute'])->default('monthly');
            $table->decimal('daily_rate_per_client', 12, 2)->default(0);
            $table->decimal('per_minute_rate', 12, 4)->default(0);
            $table->date('last_billed_date')->nullable();
            $table->boolean('auto_deduct_wallet')->default(true);
            $table->boolean('suspend_on_zero_balance')->default(true);
            $table->timestamps();
        });

        // 17. Daily billing ledger
        Schema::create('reseller_daily_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->date('billing_date');
            $table->integer('active_clients_count')->default(0);
            $table->integer('total_minutes')->default(0);
            $table->decimal('amount', 15, 2);
            $table->decimal('wallet_balance_before', 15, 2);
            $table->decimal('wallet_balance_after', 15, 2);
            $table->enum('status', ['billed', 'paid', 'overdue', 'waived'])->default('billed');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['reseller_id', 'billing_date']);
        });

        // 18. Mobile API - refresh tokens
        Schema::create('api_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 128)->unique();
            $table->string('device_name')->nullable();
            $table->string('device_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();

            $table->index('token');
        });

        // 19. Add branch_id to clients
        if (!Schema::hasColumn('clients', 'branch_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('pop_id')->nullable()->constrained()->onDelete('set null');
            });
        }

        // 20. Add branch_id to users (for manager assignment)
        if (!Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            });
        }

        // 21. Add pop_id to mikrotik_devices
        if (!Schema::hasColumn('mikrotik_devices', 'pop_id')) {
            Schema::table('mikrotik_devices', function (Blueprint $table) {
                $table->foreignId('pop_id')->nullable()->constrained()->onDelete('set null');
            });
        }

        // 22. Knowledge base categories for each panel
        if (!Schema::hasColumn('knowledge_base_articles', 'panel_access')) {
            if (Schema::hasTable('knowledge_base_articles')) {
                Schema::table('knowledge_base_articles', function (Blueprint $table) {
                    $table->json('panel_access')->nullable();
                });
            }
        }

        // 23. Add IP fields to clients for IPAM
        if (!Schema::hasColumn('clients', 'ipv6_address')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('ipv6_address')->nullable();
                $table->foreignId('ip_pool_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('ip_allocation_id')->nullable()->constrained()->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ip_allocation_id');
            $table->dropConstrainedForeignId('ip_pool_id');
            $table->dropColumn('ipv6_address');
            $table->dropConstrainedForeignId('pop_id');
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('mikrotik_devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pop_id');
        });

        Schema::dropIfExists('api_refresh_tokens');
        Schema::dropIfExists('reseller_daily_bills');
        Schema::dropIfExists('reseller_billing_configs');
        Schema::dropIfExists('voice_call_settings');
        Schema::dropIfExists('voice_call_logs');
        Schema::dropIfExists('payment_links');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('minute_sales');
        Schema::dropIfExists('minute_packages');
        Schema::dropIfExists('ip_allocations');
        Schema::dropIfExists('ip_pools');
        Schema::dropIfExists('manager_assignments');
        Schema::dropIfExists('sub_reseller_commissions');

        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn(['parent_reseller_id', 'reseller_level', 'parent_commission_rate', 'branch_id']);
        });

        Schema::dropIfExists('pops');
        Schema::dropIfExists('branches');
    }
};
