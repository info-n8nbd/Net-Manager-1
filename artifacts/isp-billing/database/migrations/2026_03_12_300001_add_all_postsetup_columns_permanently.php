<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Permanent migration for all columns previously only added by PostSetup.php.
 * Uses hasColumn() guards — safe on fresh installs AND existing databases.
 * All changes are idempotent: skip if column already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        // clients.commission_percent
        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'commission_percent')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->decimal('commission_percent', 5, 2)->nullable()->after('agent_id');
            });
        }

        // new_connection_applications columns
        if (Schema::hasTable('new_connection_applications')) {
            Schema::table('new_connection_applications', function (Blueprint $table) {
                if (!Schema::hasColumn('new_connection_applications', 'agent_id')) {
                    $table->unsignedBigInteger('agent_id')->nullable();
                }
                if (!Schema::hasColumn('new_connection_applications', 'commission_percent')) {
                    $table->decimal('commission_percent', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('new_connection_applications', 'is_free')) {
                    $table->boolean('is_free')->default(false);
                }
                if (!Schema::hasColumn('new_connection_applications', 'free_reason')) {
                    $table->string('free_reason')->nullable();
                }
            });
        }

        // districts.is_configured
        if (Schema::hasTable('districts') && !Schema::hasColumn('districts', 'is_configured')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->boolean('is_configured')->default(false)->after('is_active');
            });
        }

        // packages.show_in_public
        if (Schema::hasTable('packages') && !Schema::hasColumn('packages', 'show_in_public')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->boolean('show_in_public')->default(false);
            });
        }

        // menu_permissions.action_key
        if (Schema::hasTable('menu_permissions') && !Schema::hasColumn('menu_permissions', 'action_key')) {
            Schema::table('menu_permissions', function (Blueprint $table) {
                $table->string('action_key')->nullable();
            });
        }

        // ticket_categories.subjects
        if (Schema::hasTable('ticket_categories') && !Schema::hasColumn('ticket_categories', 'subjects')) {
            Schema::table('ticket_categories', function (Blueprint $table) {
                $table->json('subjects')->nullable();
            });
        }

        // client_service_charges columns
        if (Schema::hasTable('client_service_charges')) {
            Schema::table('client_service_charges', function (Blueprint $table) {
                if (!Schema::hasColumn('client_service_charges', 'money_receipt_no')) {
                    $table->string('money_receipt_no')->nullable();
                }
                if (!Schema::hasColumn('client_service_charges', 'transaction_id')) {
                    $table->string('transaction_id')->nullable();
                }
                if (!Schema::hasColumn('client_service_charges', 'sender_number')) {
                    $table->string('sender_number')->nullable();
                }
                if (!Schema::hasColumn('client_service_charges', 'payment_method_id')) {
                    $table->unsignedBigInteger('payment_method_id')->nullable();
                }
            });
        }

        // purchases columns
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'invoice_number')) {
                    $table->string('invoice_number')->nullable();
                }
                if (!Schema::hasColumn('purchases', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable();
                }
            });
        }

        // purchase_items.company_id
        if (Schema::hasTable('purchase_items') && !Schema::hasColumn('purchase_items', 'company_id')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable();
            });
        }

        // product_categories.company_id
        if (Schema::hasTable('product_categories') && !Schema::hasColumn('product_categories', 'company_id')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable();
            });
        }

        // products columns
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'has_serial')) {
                    $table->boolean('has_serial')->default(false);
                }
                if (!Schema::hasColumn('products', 'warranty_months')) {
                    $table->integer('warranty_months')->nullable();
                }
                if (!Schema::hasColumn('products', 'brand')) {
                    $table->string('brand')->nullable();
                }
                if (!Schema::hasColumn('products', 'model')) {
                    $table->string('model')->nullable();
                }
                if (!Schema::hasColumn('products', 'type')) {
                    $table->string('type')->nullable();
                }
                if (!Schema::hasColumn('products', 'barcode_prefix')) {
                    $table->string('barcode_prefix')->nullable();
                }
            });
        }

        // vendors columns
        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                if (!Schema::hasColumn('vendors', 'contact_person')) {
                    $table->string('contact_person')->nullable();
                }
                if (!Schema::hasColumn('vendors', 'opening_balance')) {
                    $table->decimal('opening_balance', 12, 2)->default(0);
                }
            });
        }

        // olt_devices columns
        if (Schema::hasTable('olt_devices')) {
            Schema::table('olt_devices', function (Blueprint $table) {
                if (!Schema::hasColumn('olt_devices', 'pon_type')) {
                    $table->string('pon_type', 10)->nullable();
                }
                if (!Schema::hasColumn('olt_devices', 'cpu_usage')) {
                    $table->float('cpu_usage')->nullable();
                }
                if (!Schema::hasColumn('olt_devices', 'memory_usage')) {
                    $table->float('memory_usage')->nullable();
                }
                if (!Schema::hasColumn('olt_devices', 'temperature')) {
                    $table->float('temperature')->nullable();
                }
                if (!Schema::hasColumn('olt_devices', 'system_description')) {
                    $table->text('system_description')->nullable();
                }
                if (!Schema::hasColumn('olt_devices', 'firmware_version')) {
                    $table->string('firmware_version')->nullable();
                }
                if (!Schema::hasColumn('olt_devices', 'uptime_seconds')) {
                    $table->bigInteger('uptime_seconds')->nullable();
                }
            });
        }

        // onus columns
        if (Schema::hasTable('onus')) {
            Schema::table('onus', function (Blueprint $table) {
                if (!Schema::hasColumn('onus', 'distance')) {
                    $table->float('distance')->nullable();
                }
                if (!Schema::hasColumn('onus', 'tx_bias')) {
                    $table->float('tx_bias')->nullable();
                }
                if (!Schema::hasColumn('onus', 'voltage')) {
                    $table->float('voltage')->nullable();
                }
            });
        }

        // sales.payment_reference (extra check if sales table was pre-existing)
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'payment_reference')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('payment_reference')->nullable();
            });
        }

        // sale_items.brand, sale_items.model (extra check if sale_items was pre-existing)
        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_items', 'brand')) {
                    $table->string('brand')->nullable();
                }
                if (!Schema::hasColumn('sale_items', 'model')) {
                    $table->string('model')->nullable();
                }
            });
        }

        // resellers.mikrotik_device_id
        if (Schema::hasTable('resellers') && !Schema::hasColumn('resellers', 'mikrotik_device_id')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->unsignedBigInteger('mikrotik_device_id')->nullable();
            });
        }

        // collection_transfers.to_user_id
        if (Schema::hasTable('collection_transfers') && !Schema::hasColumn('collection_transfers', 'to_user_id')) {
            Schema::table('collection_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('to_user_id')->nullable();
                $table->foreign('to_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // collection_transfers.to_account_id — make nullable if NOT NULL
        if (Schema::hasTable('collection_transfers') && Schema::hasColumn('collection_transfers', 'to_account_id')) {
            $col = collect(DB::select(
                "SELECT is_nullable FROM information_schema.columns WHERE table_name = 'collection_transfers' AND column_name = 'to_account_id'"
            ))->first();
            if ($col && $col->is_nullable === 'NO') {
                DB::statement('ALTER TABLE collection_transfers DROP CONSTRAINT IF EXISTS collection_transfers_to_account_id_foreign');
                DB::statement('ALTER TABLE collection_transfers ALTER COLUMN to_account_id DROP NOT NULL');
                try {
                    Schema::table('collection_transfers', function (Blueprint $table) {
                        $table->foreign('to_account_id')->references('id')->on('accounts')->nullOnDelete();
                    });
                } catch (\Throwable $e) {
                    // FK may already exist
                }
            }
        }
    }

    public function down(): void
    {
        // Columns added — down() intentionally left as no-op to preserve data safety
        // Remove manually if needed
    }
};
