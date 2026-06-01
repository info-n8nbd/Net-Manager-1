<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'two_factor_secret')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('two_factor_secret')->nullable()->after('password');
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
                $table->string('password_changed_at')->nullable()->after('two_factor_confirmed_at');
                $table->integer('failed_login_attempts')->default(0)->after('password_changed_at');
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
                $table->string('last_login_ip')->nullable();
                $table->timestamp('last_login_at')->nullable();
            });
        }

        if (!Schema::hasTable('login_logs')) {
            Schema::create('login_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('email')->nullable();
                $table->string('ip_address', 45);
                $table->string('user_agent')->nullable();
                $table->string('status'); // success, failed, blocked, 2fa_required
                $table->string('failure_reason')->nullable();
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
                $table->index(['ip_address', 'created_at']);
            });
        }

        if (!Schema::hasTable('password_policies')) {
            Schema::create('password_policies', function (Blueprint $table) {
                $table->id();
                $table->integer('min_length')->default(8);
                $table->boolean('require_uppercase')->default(true);
                $table->boolean('require_lowercase')->default(true);
                $table->boolean('require_numbers')->default(true);
                $table->boolean('require_special')->default(false);
                $table->integer('password_expiry_days')->default(0);
                $table->integer('max_login_attempts')->default(5);
                $table->integer('lockout_duration_minutes')->default(30);
                $table->boolean('enforce_2fa_admin')->default(false);
                $table->boolean('enforce_2fa_all')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('api_tokens')) {
            Schema::create('api_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->json('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->index('token');
            });
        }

        if (!Schema::hasColumn('clients', 'deleted_reason')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('deleted_reason')->nullable();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('deleted_at_custom')->nullable();
            });
        }

        if (!Schema::hasTable('client_recovery_logs')) {
            Schema::create('client_recovery_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('recovered_by')->constrained('users')->cascadeOnDelete();
                $table->string('previous_status');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_settings')) {
            Schema::create('whatsapp_settings', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->default('whatsapp_business'); // whatsapp_business, twilio, 360dialog, wati
                $table->string('api_url')->nullable();
                $table->string('api_key')->nullable();
                $table->string('phone_number_id')->nullable();
                $table->string('business_account_id')->nullable();
                $table->string('access_token')->nullable();
                $table->boolean('is_active')->default(false);
                $table->integer('daily_limit')->default(1000);
                $table->integer('sent_today')->default(0);
                $table->json('config')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_templates')) {
            Schema::create('whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('content');
                $table->string('language')->default('bn');
                $table->string('category')->default('utility'); // utility, marketing, authentication
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->json('variables')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_logs')) {
            Schema::create('whatsapp_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->string('phone');
                $table->string('template_code')->nullable();
                $table->text('message');
                $table->string('status')->default('pending'); // pending, sent, delivered, read, failed
                $table->string('message_id')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->decimal('cost', 8, 4)->default(0);
                $table->timestamps();
                $table->index(['client_id', 'created_at']);
                $table->index(['status', 'created_at']);
            });
        }

        if (!Schema::hasTable('scratch_cards')) {
            Schema::create('scratch_card_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_no')->unique();
                $table->string('name');
                $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('amount', 10, 2);
                $table->integer('validity_days')->default(30);
                $table->integer('total_cards');
                $table->integer('used_cards')->default(0);
                $table->integer('active_cards')->default(0);
                $table->integer('expired_cards')->default(0);
                $table->string('status')->default('active'); // active, inactive, expired
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });

            Schema::create('scratch_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_id')->constrained('scratch_card_batches')->cascadeOnDelete();
                $table->string('card_number', 20)->unique();
                $table->string('pin', 64);
                $table->string('serial_number')->unique();
                $table->decimal('amount', 10, 2);
                $table->integer('validity_days')->default(30);
                $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('active'); // active, used, expired, disabled
                $table->foreignId('used_by_client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->timestamp('used_at')->nullable();
                $table->foreignId('distributor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('distributed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->index(['card_number', 'pin']);
                $table->index('serial_number');
                $table->index(['status', 'batch_id']);
            });

            Schema::create('card_distributors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('address')->nullable();
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->decimal('total_sales', 12, 2)->default(0);
                $table->decimal('total_commission', 12, 2)->default(0);
                $table->decimal('balance', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            Schema::create('card_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scratch_card_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('distributor_id')->nullable()->constrained('card_distributors')->nullOnDelete();
                $table->string('transaction_type'); // redemption, distribution, disable, refund
                $table->decimal('amount', 10, 2);
                $table->string('status')->default('success');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hotspot_templates')) {
            Schema::create('hotspot_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->longText('html_content');
                $table->text('css_content')->nullable();
                $table->text('js_content')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('background_image')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(false);
                $table->boolean('is_default')->default(false);
                $table->foreignId('mikrotik_device_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vpn_accounts')) {
            Schema::create('vpn_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->string('vpn_type'); // l2tp, pptp, sstp, ovpn
                $table->string('username')->unique();
                $table->string('password');
                $table->string('local_address')->nullable();
                $table->string('remote_address')->nullable();
                $table->string('dns_server')->nullable();
                $table->string('profile')->nullable();
                $table->string('service')->default('any');
                $table->boolean('is_active')->default(true);
                $table->foreignId('mikrotik_device_id')->nullable()->constrained()->nullOnDelete();
                $table->string('mikrotik_id')->nullable();
                $table->decimal('monthly_fee', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('port_forwards')) {
            Schema::create('port_forwards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('protocol')->default('tcp'); // tcp, udp, both
                $table->integer('external_port');
                $table->string('internal_ip');
                $table->integer('internal_port');
                $table->string('wan_interface')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('mikrotik_device_id')->nullable()->constrained()->nullOnDelete();
                $table->string('mikrotik_rule_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('backups')) {
            Schema::create('backups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type'); // database, files, full, config
                $table->string('file_path')->nullable();
                $table->bigInteger('file_size')->default(0);
                $table->string('status')->default('pending'); // pending, running, completed, failed
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('knowledge_base_categories')) {
            Schema::create('knowledge_base_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->default('fas fa-book');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            Schema::create('knowledge_base_articles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('knowledge_base_categories')->cascadeOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content');
                $table->string('audience')->default('all'); // all, admin, technician, client, reseller
                $table->integer('views')->default(0);
                $table->boolean('is_published')->default(true);
                $table->boolean('is_featured')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['category_id', 'is_published']);
            });
        }

        if (!Schema::hasColumn('clients', 'ipv6_address')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('ipv6_address')->nullable();
                $table->string('ipv6_prefix')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('knowledge_base_categories');
        Schema::dropIfExists('backups');
        Schema::dropIfExists('port_forwards');
        Schema::dropIfExists('vpn_accounts');
        Schema::dropIfExists('hotspot_templates');
        Schema::dropIfExists('card_transactions');
        Schema::dropIfExists('card_distributors');
        Schema::dropIfExists('scratch_cards');
        Schema::dropIfExists('scratch_card_batches');
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_settings');
        Schema::dropIfExists('client_recovery_logs');
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('password_policies');
        Schema::dropIfExists('login_logs');

        if (Schema::hasColumn('users', 'two_factor_secret')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'password_changed_at', 'failed_login_attempts', 'locked_until', 'last_login_ip', 'last_login_at']);
            });
        }
        if (Schema::hasColumn('clients', 'deleted_reason')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn(['deleted_reason', 'deleted_by', 'deleted_at_custom']);
            });
        }
        if (Schema::hasColumn('clients', 'ipv6_address')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn(['ipv6_address', 'ipv6_prefix']);
            });
        }
    }
};
