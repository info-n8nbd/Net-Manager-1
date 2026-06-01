<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class PostSetup extends Command
{
    protected $signature = 'app:post-setup';
    protected $description = 'Add all database columns/tables that were added outside of migrations. Safe to run multiple times.';

    public function handle()
    {
        $this->info('Running post-setup checks...');
        $changes = 0;

        $this->cleanupLegacyRoles($changes);

        if (!Schema::hasTable('dropdown_options')) {
            Schema::create('dropdown_options', function (Blueprint $table) {
                $table->id();
                $table->string('group_name', 100);
                $table->string('label', 255);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            $this->info('  ✓ Created dropdown_options table');
            $changes++;
        }

        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'commission_percent')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->decimal('commission_percent', 5, 2)->nullable()->after('agent_id');
            });
            $this->info('  ✓ Added clients.commission_percent');
            $changes++;
        }

        if (Schema::hasTable('new_connection_applications')) {
            if (!Schema::hasColumn('new_connection_applications', 'agent_id')) {
                Schema::table('new_connection_applications', function (Blueprint $table) {
                    $table->unsignedBigInteger('agent_id')->nullable();
                });
                $this->info('  ✓ Added new_connection_applications.agent_id');
                $changes++;
            }
            if (!Schema::hasColumn('new_connection_applications', 'commission_percent')) {
                Schema::table('new_connection_applications', function (Blueprint $table) {
                    $table->decimal('commission_percent', 5, 2)->nullable();
                });
                $this->info('  ✓ Added new_connection_applications.commission_percent');
                $changes++;
            }
            if (!Schema::hasColumn('new_connection_applications', 'is_free')) {
                Schema::table('new_connection_applications', function (Blueprint $table) {
                    $table->boolean('is_free')->default(false);
                    $table->string('free_reason')->nullable();
                });
                $this->info('  ✓ Added new_connection_applications.is_free, free_reason');
                $changes++;
            }
        }

        if (Schema::hasTable('districts') && !Schema::hasColumn('districts', 'is_configured')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->boolean('is_configured')->default(false)->after('is_active');
            });
            $this->info('  ✓ Added districts.is_configured');
            $changes++;
        }

        if (Schema::hasTable('packages') && !Schema::hasColumn('packages', 'show_in_public')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->boolean('show_in_public')->default(false);
            });
            $this->info('  ✓ Added packages.show_in_public');
            $changes++;
        }

        if (Schema::hasTable('menu_permissions') && !Schema::hasColumn('menu_permissions', 'action_key')) {
            Schema::table('menu_permissions', function (Blueprint $table) {
                $table->string('action_key')->nullable();
            });
            $this->info('  ✓ Added menu_permissions.action_key');
            $changes++;
        }

        if (Schema::hasTable('ticket_categories') && !Schema::hasColumn('ticket_categories', 'subjects')) {
            Schema::table('ticket_categories', function (Blueprint $table) {
                $table->json('subjects')->nullable();
            });
            $this->info('  ✓ Added ticket_categories.subjects');
            $changes++;
        }

        if (Schema::hasTable('client_service_charges')) {
            $addCols = [];
            if (!Schema::hasColumn('client_service_charges', 'money_receipt_no')) {
                $addCols[] = 'money_receipt_no';
            }
            if (!Schema::hasColumn('client_service_charges', 'transaction_id')) {
                $addCols[] = 'transaction_id';
            }
            if (!Schema::hasColumn('client_service_charges', 'sender_number')) {
                $addCols[] = 'sender_number';
            }
            if (!Schema::hasColumn('client_service_charges', 'payment_method_id')) {
                $addCols[] = 'payment_method_id';
            }
            if (!empty($addCols)) {
                Schema::table('client_service_charges', function (Blueprint $table) use ($addCols) {
                    if (in_array('money_receipt_no', $addCols)) {
                        $table->string('money_receipt_no')->nullable();
                    }
                    if (in_array('transaction_id', $addCols)) {
                        $table->string('transaction_id')->nullable();
                    }
                    if (in_array('sender_number', $addCols)) {
                        $table->string('sender_number')->nullable();
                    }
                    if (in_array('payment_method_id', $addCols)) {
                        $table->unsignedBigInteger('payment_method_id')->nullable();
                    }
                });
                $this->info('  ✓ Added client_service_charges columns: ' . implode(', ', $addCols));
                $changes++;
            }
        }

        if (!Schema::hasTable('user_menu_permissions')) {
            Schema::create('user_menu_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('menu_key');
                $table->string('submenu_key')->nullable();
                $table->string('action_key')->nullable();
                $table->string('override', 10);
                $table->unsignedBigInteger('company_id')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'menu_key', 'submenu_key']);
                $table->unique(['user_id', 'menu_key', 'submenu_key', 'action_key'], 'ump_unique');
            });
            $this->info('  ✓ Created user_menu_permissions table');
            $changes++;
        }

        if (Schema::hasTable('purchases')) {
            if (!Schema::hasColumn('purchases', 'invoice_number')) {
                Schema::table('purchases', function (Blueprint $table) {
                    $table->string('invoice_number')->nullable();
                });
                $this->info('  ✓ Added purchases.invoice_number');
                $changes++;
            }
            if (!Schema::hasColumn('purchases', 'company_id')) {
                Schema::table('purchases', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable();
                });
                $this->info('  ✓ Added purchases.company_id');
                $changes++;
            }
        }

        if (Schema::hasTable('purchase_items') && !Schema::hasColumn('purchase_items', 'company_id')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable();
            });
            $this->info('  ✓ Added purchase_items.company_id');
            $changes++;
        }

        if (Schema::hasTable('product_categories') && !Schema::hasColumn('product_categories', 'company_id')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable();
            });
            $this->info('  ✓ Added product_categories.company_id');
            $changes++;
        }

        if (Schema::hasTable('products')) {
            $productCols = [];
            if (!Schema::hasColumn('products', 'has_serial')) $productCols[] = 'has_serial';
            if (!Schema::hasColumn('products', 'warranty_months')) $productCols[] = 'warranty_months';
            if (!Schema::hasColumn('products', 'brand')) $productCols[] = 'brand';
            if (!Schema::hasColumn('products', 'model')) $productCols[] = 'model';
            if (!Schema::hasColumn('products', 'type')) $productCols[] = 'type';
            if (!Schema::hasColumn('products', 'barcode_prefix')) $productCols[] = 'barcode_prefix';
            if (!empty($productCols)) {
                Schema::table('products', function (Blueprint $table) use ($productCols) {
                    if (in_array('has_serial', $productCols)) $table->boolean('has_serial')->default(false);
                    if (in_array('warranty_months', $productCols)) $table->integer('warranty_months')->nullable();
                    if (in_array('brand', $productCols)) $table->string('brand')->nullable();
                    if (in_array('model', $productCols)) $table->string('model')->nullable();
                    if (in_array('type', $productCols)) $table->string('type')->nullable();
                    if (in_array('barcode_prefix', $productCols)) $table->string('barcode_prefix')->nullable();
                });
                $this->info('  ✓ Added products columns: ' . implode(', ', $productCols));
                $changes++;
            }
        }

        if (Schema::hasTable('vendors')) {
            if (!Schema::hasColumn('vendors', 'contact_person')) {
                Schema::table('vendors', function (Blueprint $table) {
                    $table->string('contact_person')->nullable();
                });
                $this->info('  ✓ Added vendors.contact_person');
                $changes++;
            }
            if (!Schema::hasColumn('vendors', 'opening_balance')) {
                Schema::table('vendors', function (Blueprint $table) {
                    $table->decimal('opening_balance', 12, 2)->default(0);
                });
                $this->info('  ✓ Added vendors.opening_balance');
                $changes++;
            }
        }

        if (Schema::hasTable('olt_devices') && !Schema::hasColumn('olt_devices', 'pon_type')) {
            Schema::table('olt_devices', function (Blueprint $table) {
                $table->string('pon_type', 10)->nullable()->after('vendor');
            });
            $this->info('  ✓ Added olt_devices.pon_type');
            $changes++;
        }

        if (Schema::hasTable('onus')) {
            if (!Schema::hasColumn('onus', 'distance')) {
                Schema::table('onus', function (Blueprint $table) {
                    $table->float('distance')->nullable()->after('tx_power');
                });
                $this->info('  ✓ Added onus.distance');
                $changes++;
            }
            if (!Schema::hasColumn('onus', 'tx_bias')) {
                Schema::table('onus', function (Blueprint $table) {
                    $table->float('tx_bias')->nullable()->after('distance');
                });
                $this->info('  ✓ Added onus.tx_bias');
                $changes++;
            }
            if (!Schema::hasColumn('onus', 'voltage')) {
                Schema::table('onus', function (Blueprint $table) {
                    $table->float('voltage')->nullable()->after('tx_bias');
                });
                $this->info('  ✓ Added onus.voltage');
                $changes++;
            }
        }

        try {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
            $user = \App\Models\User::where('email', 'superadmin@isp.com')->first();
            if ($user) {
                if ($user->user_type !== 'super_admin') {
                    $user->update(['user_type' => 'super_admin']);
                }
                if (!$user->hasRole('Super Admin')) {
                    $user->assignRole('Super Admin');
                }
                $this->info('  ✓ Super Admin verified: ' . $user->email);
            } else {
                $superAdmin = \App\Models\User::create([
                    'name' => 'Super Admin',
                    'email' => 'superadmin@isp.com',
                    'password' => \Illuminate\Support\Facades\Hash::make('SuperAdmin@123'),
                    'email_verified_at' => now(),
                    'user_type' => 'super_admin',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
                $superAdmin->assignRole($superAdminRole);
                $this->info('  ✓ Created superadmin@isp.com (SuperAdmin@123)');
                $changes++;
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Super Admin check: ' . $e->getMessage());
        }

        // Ensure Company 1 (Default ISP) always has an admin user (admin@isp.com)
        try {
            $company1 = DB::table('companies')->where('id', 1)->first();
            if ($company1) {
                $adminUser = DB::table('users')->where('email', 'admin@isp.com')->first();
                if (!$adminUser) {
                    $adminId = DB::table('users')->insertGetId([
                        'name' => 'ISP Admin',
                        'email' => 'admin@isp.com',
                        'password' => \Illuminate\Support\Facades\Hash::make('Test@123'),
                        'user_type' => 'admin',
                        'company_id' => 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
                    $adminUserModel = \App\Models\User::find($adminId);
                    if ($adminUserModel) {
                        $adminUserModel->assignRole($adminRole);
                    }
                    $this->info('  ✓ Created admin@isp.com for Company 1');
                    $changes++;
                } else {
                    $needsUpdate = false;
                    $updateData = [];
                    if ($adminUser->user_type !== 'admin') {
                        $updateData['user_type'] = 'admin';
                        $needsUpdate = true;
                    }
                    if (empty($adminUser->company_id) || $adminUser->company_id != 1) {
                        $updateData['company_id'] = 1;
                        $needsUpdate = true;
                    }
                    if ($needsUpdate) {
                        DB::table('users')->where('email', 'admin@isp.com')->update($updateData);
                        $this->info('  ✓ Fixed admin@isp.com (company_id/user_type corrected)');
                        $changes++;
                    }

                    // Ensure admin role is assigned
                    $adminUserModel = \App\Models\User::where('email', 'admin@isp.com')->first();
                    if ($adminUserModel && !$adminUserModel->hasRole('Administrator')) {
                        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
                        $adminUserModel->assignRole($adminRole);
                        $this->info('  ✓ Assigned admin role to admin@isp.com');
                        $changes++;
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Company 1 admin check: ' . $e->getMessage());
        }

        // Fix technician_assignments status check constraint to include 'removed'
        try {
            if (Schema::hasTable('technician_assignments')) {
                $allowed = DB::selectOne("
                    SELECT pg_get_constraintdef(oid) as def FROM pg_constraint
                    WHERE conrelid = 'technician_assignments'::regclass
                      AND conname = 'technician_assignments_status_check'
                ");
                if ($allowed && strpos($allowed->def, 'removed') === false) {
                    DB::statement("ALTER TABLE technician_assignments DROP CONSTRAINT IF EXISTS technician_assignments_status_check");
                    DB::statement("ALTER TABLE technician_assignments ADD CONSTRAINT technician_assignments_status_check CHECK (status IN ('assigned','in_progress','completed','reassigned','removed'))");
                    $this->info('  ✓ Updated technician_assignments status constraint to include removed');
                    $changes++;
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  ! technician_assignments constraint fix: ' . $e->getMessage());
        }

        // Fix orphaned non-super-admin users with null company_id → assign to company_id=1
        try {
            $fixed = DB::table('users')
                ->whereNull('company_id')
                ->where('user_type', '!=', 'super_admin')
                ->update(['company_id' => 1]);
            if ($fixed > 0) {
                $this->info("  ✓ Fixed {$fixed} orphaned user(s) → assigned company_id=1");
                $changes++;
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Orphaned users fix: ' . $e->getMessage());
        }

        // Auto-verify email for all internal (non-client) users — portals require email verification
        try {
            $verified = DB::table('users')
                ->whereNull('email_verified_at')
                ->where('user_type', '!=', 'client')
                ->update(['email_verified_at' => now()]);
            if ($verified > 0) {
                $this->info("  ✓ Auto-verified email for {$verified} internal user(s)");
                $changes++;
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Email auto-verify: ' . $e->getMessage());
        }

        // Fix card_distributor user_type inconsistency (hyphen → underscore)
        try {
            $fixed = DB::table('users')
                ->where('user_type', 'card-distributor')
                ->update(['user_type' => 'card_distributor']);
            if ($fixed > 0) {
                $this->info("  ✓ Fixed {$fixed} card_distributor user_type (hyphen→underscore)");
                $changes++;
            }
        } catch (\Throwable $e) {
            $this->warn('  ! card_distributor user_type fix: ' . $e->getMessage());
        }

        $this->ensureTestUsers($changes);

        // Fix zones/areas/boxes/departments/employees/profiles with null company_id → assign to company_id=1
        foreach (['zones', 'areas', 'boxes', 'departments', 'designations', 'mikrotik_profiles', 'packages', 'manager_assignments'] as $tbl) {
            try {
                if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'company_id')) {
                    $q = DB::table($tbl)->whereNull('company_id');
                    if (Schema::hasColumn($tbl, 'reseller_id')) {
                        $q->whereNull('reseller_id');
                    }
                    $fixed = $q->update(['company_id' => 1]);
                    if ($fixed > 0) {
                        $this->info("  ✓ Fixed {$fixed} {$tbl} with null company_id → assigned company_id=1");
                        $changes++;
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("  ! {$tbl} company_id fix: " . $e->getMessage());
            }
        }

        // Fix clients with null branch_id → assign to branch_id=1 (main branch) for company 1
        try {
            if (Schema::hasTable('clients') && Schema::hasTable('branches')) {
                $mainBranch = DB::table('branches')->where('company_id', 1)->orderBy('id')->value('id') ?? 1;
                $fixed = DB::table('clients')
                    ->whereNull('branch_id')
                    ->where('company_id', 1)
                    ->update(['branch_id' => $mainBranch]);
                if ($fixed > 0) {
                    $this->info("  ✓ Fixed {$fixed} clients with null branch_id → assigned branch_id={$mainBranch}");
                    $changes++;
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Clients branch_id fix: ' . $e->getMessage());
        }

        // Fix employees with null company_id but with a user that has company_id
        try {
            if (Schema::hasTable('employees') && Schema::hasTable('users')) {
                $nullEmployees = DB::table('employees as e')
                    ->join('users as u', 'e.user_id', '=', 'u.id')
                    ->whereNull('e.company_id')
                    ->whereNotNull('u.company_id')
                    ->select('e.id as emp_id', 'u.company_id', 'e.employee_id')
                    ->get();
                $fixedCount = 0;
                foreach ($nullEmployees as $emp) {
                    try {
                        // Check for employee_id conflict within the company
                        $conflict = DB::table('employees')
                            ->where('company_id', $emp->company_id)
                            ->where('employee_id', $emp->employee_id)
                            ->exists();
                        if ($conflict) {
                            // Generate a new unique employee_id to avoid constraint violation
                            $maxId = DB::table('employees')
                                ->where('company_id', $emp->company_id)
                                ->max(DB::raw('CAST(employee_id AS INTEGER)')) ?? 0;
                            DB::table('employees')->where('id', $emp->emp_id)
                                ->update(['company_id' => $emp->company_id, 'employee_id' => $maxId + 1]);
                        } else {
                            DB::table('employees')->where('id', $emp->emp_id)
                                ->update(['company_id' => $emp->company_id]);
                        }
                        $fixedCount++;
                    } catch (\Throwable $inner) {
                        // Skip individual row if still conflicts
                    }
                }
                if ($fixedCount > 0) {
                    $this->info("  ✓ Fixed {$fixedCount} employees with null company_id");
                    $changes++;
                }
                // Also fix users.company_id if NULL but employee has company_id
                DB::statement("
                    UPDATE users u SET company_id = e.company_id
                    FROM employees e WHERE e.user_id = u.id
                    AND u.company_id IS NULL AND e.company_id IS NOT NULL
                ");
            }
        } catch (\Throwable $e) {
            $this->warn("  ! Employees company_id from user fix: " . $e->getMessage());
        }

        // Sync users.name from employees.name (keep display names consistent across portals)
        try {
            if (Schema::hasTable('employees') && Schema::hasTable('users')) {
                $diffCount = DB::selectOne("
                    SELECT COUNT(*) as c FROM employees e
                    JOIN users u ON u.id = e.user_id
                    WHERE u.name IS DISTINCT FROM e.name
                      AND e.name IS NOT NULL AND e.name != ''
                ")->c ?? 0;
                if ($diffCount > 0) {
                    DB::statement("
                        UPDATE users u SET name = e.name
                        FROM employees e
                        WHERE e.user_id = u.id
                          AND u.name IS DISTINCT FROM e.name
                          AND e.name IS NOT NULL AND e.name != ''
                    ");
                    $this->info("  ✓ Synced {$diffCount} user name(s) from employee records");
                    $changes++;
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Employee→user name sync: ' . $e->getMessage());
        }

        // Ensure new roles (Assistant Admin, Assistant Manager) exist with correct Spatie permissions
        try {
            if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
                $newRoles = [
                    'Admin Support'     => 'web',
                    'Assistant Manager' => 'web',
                ];
                $rolesCreated = 0;
                foreach ($newRoles as $roleName => $guard) {
                    $role = \Spatie\Permission\Models\Role::firstOrCreate(
                        ['name' => $roleName, 'guard_name' => $guard]
                    );
                    if ($role->wasRecentlyCreated) {
                        // Give new role same Spatie permissions as admin (full access)
                        $adminRole = \Spatie\Permission\Models\Role::where('name', 'Administrator')->first();
                        if ($adminRole) {
                            $role->syncPermissions($adminRole->permissions);
                        }
                        // Seed menu permission defaults for this role
                        \App\Services\MenuPermissionService::seedRoleDefaults($role->id, $roleName);
                        $rolesCreated++;
                    }
                }
                if ($rolesCreated > 0) {
                    $this->info("  ✓ Created {$rolesCreated} new role(s): Assistant Admin, Assistant Manager");
                    $changes++;
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  ! New role creation: ' . $e->getMessage());
        }

        // Fix employees with null company_id → assign to company_id=1 (skip duplicates)
        try {
            if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'company_id')) {
                $orphans = DB::table('employees')->whereNull('company_id')->get();
                $fixed = 0;
                foreach ($orphans as $emp) {
                    // Check if updating would cause a unique conflict on (company_id, employee_id)
                    $conflict = Schema::hasColumn('employees', 'employee_id')
                        && DB::table('employees')
                            ->where('company_id', 1)
                            ->where('employee_id', $emp->employee_id)
                            ->exists();
                    if (!$conflict) {
                        DB::table('employees')->where('id', $emp->id)->update(['company_id' => 1]);
                        $fixed++;
                    }
                }
                if ($fixed > 0) {
                    $this->info("  ✓ Fixed {$fixed} orphaned employee(s) → assigned company_id=1");
                    $changes++;
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Orphaned employees fix: ' . $e->getMessage());
        }

        // Ensure payment_method_perms table has correct structure (migration safety net)
        try {
            if (!Schema::hasTable('payment_method_perms')) {
                Schema::create('payment_method_perms', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('payment_method_id');
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->unsignedBigInteger('role_id')->nullable();
                    $table->unsignedBigInteger('company_id')->nullable();
                    $table->timestamps();
                });
                $this->info('  ✓ Created payment_method_perms table (safety net)');
                $changes++;
            }
        } catch (\Throwable $e) {
            $this->warn('  ! payment_method_perms table: ' . $e->getMessage());
        }

        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('invoice_number')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                $table->string('customer_type')->default('walk_in');
                $table->date('sale_date');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax_percent', 5, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->decimal('paid', 12, 2)->default(0);
                $table->decimal('due', 12, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('company_id');
                $table->index('client_id');
                $table->index('status');
            });
            $this->info('  ✓ Created sales table');
            $changes++;
        }

        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('sale_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('product_name');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('serial_number')->nullable();
                $table->timestamps();
                $table->index('sale_id');
                $table->index('product_id');
            });
            $this->info('  ✓ Created sale_items table');
            $changes++;
        }

        if (Schema::hasTable('sales')) {
            if (!Schema::hasColumn('sales', 'payment_reference')) {
                Schema::table('sales', function (Blueprint $table) {
                    $table->string('payment_reference')->nullable()->after('payment_method');
                });
                $this->info('  ✓ Added payment_reference column to sales');
                $changes++;
            }
        }

        if (Schema::hasTable('sale_items')) {
            if (!Schema::hasColumn('sale_items', 'brand')) {
                Schema::table('sale_items', function (Blueprint $table) {
                    $table->string('brand')->nullable()->after('serial_number');
                    $table->string('model')->nullable()->after('brand');
                });
                $this->info('  ✓ Added brand, model columns to sale_items');
                $changes++;
            }
        }

        if (Schema::hasTable('olt_devices')) {
            $oltSnmpCols = [
                'cpu_usage' => 'float|nullable',
                'memory_usage' => 'float|nullable',
                'temperature' => 'float|nullable',
                'system_description' => 'text|nullable',
                'firmware_version' => 'string|nullable',
                'uptime_seconds' => 'bigInteger|nullable',
            ];
            foreach ($oltSnmpCols as $col => $def) {
                if (!Schema::hasColumn('olt_devices', $col)) {
                    Schema::table('olt_devices', function (Blueprint $table) use ($col, $def) {
                        $parts = explode('|', $def);
                        $method = $parts[0];
                        $column = $table->$method($col);
                        if (in_array('nullable', $parts)) $column->nullable();
                    });
                    $this->info("  ✓ Added olt_devices.{$col}");
                    $changes++;
                }
            }
        }

        if (!Schema::hasTable('olt_snmp_stats')) {
            Schema::create('olt_snmp_stats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('olt_device_id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->float('cpu_usage')->nullable();
                $table->float('memory_usage')->nullable();
                $table->float('temperature')->nullable();
                $table->bigInteger('uptime_seconds')->nullable();
                $table->integer('total_onu')->default(0);
                $table->integer('online_onu')->default(0);
                $table->integer('offline_onu')->default(0);
                $table->float('avg_rx_power')->nullable();
                $table->timestamp('polled_at')->nullable();
                $table->timestamps();
                $table->index(['olt_device_id', 'polled_at']);
                $table->index('company_id');
            });
            $this->info('  ✓ Created olt_snmp_stats table');
            $changes++;
        }

        if (!Schema::hasTable('olt_traffic_stats')) {
            Schema::create('olt_traffic_stats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('olt_device_id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->integer('port_no');
                $table->bigInteger('in_octets')->default(0);
                $table->bigInteger('out_octets')->default(0);
                $table->bigInteger('in_rate_bps')->default(0);
                $table->bigInteger('out_rate_bps')->default(0);
                $table->integer('total_onu')->default(0);
                $table->integer('online_onu')->default(0);
                $table->timestamp('polled_at')->nullable();
                $table->timestamps();
                $table->index(['olt_device_id', 'port_no', 'polled_at'], 'olt_traffic_device_port_poll');
                $table->index('company_id');
            });
            $this->info('  ✓ Created olt_traffic_stats table');
            $changes++;
        }

        $this->seedBangladeshDistricts($changes);
        $this->seedDepartmentsAndDesignations($changes);

        if (Schema::hasTable('fiber_histories')) {
            $missingAssigned = DB::table('fiber_histories')
                ->where('type', 'fiber_out')
                ->where('details', 'NOT LIKE', '%Assigned By:%')
                ->whereNotNull('details')
                ->get();
            foreach ($missingAssigned as $fh) {
                $superAdmin = DB::table('users')->where('email', 'superadmin@isp.com')->value('name') ?? 'Super Admin';
                DB::table('fiber_histories')->where('id', $fh->id)->update([
                    'details' => $fh->details . ' | Assigned By: ' . $superAdmin,
                ]);
                $changes++;
            }
            if ($missingAssigned->count() > 0) {
                $this->info("  ✓ Backfilled {$missingAssigned->count()} fiber_out records with Assigned By");
            }
        }

        if (Schema::hasTable('menu_permissions')) {
            $movedRestore = DB::table('menu_permissions')
                ->where('menu_key', 'client_recovery')
                ->update(['menu_key' => 'client', 'submenu_key' => 'client_restore']);
            if ($movedRestore > 0) {
                $this->info("  ✓ Moved client_recovery to client.client_restore menu ({$movedRestore} rows)");
                $changes += $movedRestore;
            }
        }
        if (Schema::hasTable('user_menu_permissions')) {
            $movedRestore2 = DB::table('user_menu_permissions')
                ->where('menu_key', 'client_recovery')
                ->update(['menu_key' => 'client', 'submenu_key' => 'client_restore']);
            if ($movedRestore2 > 0) {
                $this->info("  ✓ Moved user client_recovery permissions to client.client_restore ({$movedRestore2} rows)");
                $changes += $movedRestore2;
            }
        }

        if (Schema::hasTable('menu_permissions')) {
            $moved = DB::table('menu_permissions')
                ->where('menu_key', 'settings')
                ->where('submenu_key', 'dropdown_options')
                ->update(['menu_key' => 'configuration']);
            if ($moved > 0) {
                $this->info("  ✓ Moved dropdown_options from settings to configuration menu ({$moved} rows)");
                $changes += $moved;
            }
        }
        if (Schema::hasTable('user_menu_permissions')) {
            $moved2 = DB::table('user_menu_permissions')
                ->where('menu_key', 'settings')
                ->where('submenu_key', 'dropdown_options')
                ->update(['menu_key' => 'configuration']);
            if ($moved2 > 0) {
                $this->info("  ✓ Moved user dropdown_options permissions to configuration menu ({$moved2} rows)");
                $changes += $moved2;
            }
        }

        if (Schema::hasTable('resellers') && !Schema::hasColumn('resellers', 'mikrotik_device_id')) {
            Schema::table('resellers', function ($table) {
                $table->unsignedBigInteger('mikrotik_device_id')->nullable()->after('zone_id');
            });
            $this->info('  ✓ Added mikrotik_device_id column to resellers table');
            $changes++;
        }

        try {
            $storePerms = ['store.view', 'store.create', 'store.edit', 'store.delete'];
            $salesPerms = ['sales.view', 'sales.create', 'sales.edit', 'sales.delete'];
            $inventoryPerms = ['inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete'];
            $resellerPerms = [
                'reseller.dashboard', 'reseller.clients', 'reseller.client_edit',
                'reseller.client_restore', 'reseller.collection', 'reseller.due_report',
                'reseller.packages', 'reseller.tickets', 'reseller.wallet', 'reseller.device_control',
            ];
            $technicianPerms = [
                'technician.dashboard', 'technician.clients',
                'technician.client.create', 'technician.client.edit',
                'technician.collection', 'technician.tickets', 'technician.new_connection',
                'technician.due_report', 'technician.device_control',
            ];
            $allNewPerms = array_merge($storePerms, $salesPerms, $inventoryPerms, $resellerPerms, $technicianPerms);
            foreach ($allNewPerms as $permName) {
                \Spatie\Permission\Models\Permission::firstOrCreate(
                    ['name' => $permName, 'guard_name' => 'web']
                );
            }

            // Ensure technician role has all technician permissions assigned
            $techRole = \Spatie\Permission\Models\Role::where('name', 'Field Technician')->first();
            if ($techRole) {
                foreach ($technicianPerms as $permName) {
                    $perm = \Spatie\Permission\Models\Permission::where('name', $permName)->first();
                    if ($perm && !$techRole->hasPermissionTo($perm)) {
                        $techRole->givePermissionTo($perm);
                    }
                }
            }

            // Ensure reseller role has all reseller permissions assigned
            $resellerRole = \Spatie\Permission\Models\Role::where('name', 'Reseller')->first();
            if ($resellerRole) {
                foreach ($resellerPerms as $permName) {
                    $perm = \Spatie\Permission\Models\Permission::where('name', $permName)->first();
                    if ($perm && !$resellerRole->hasPermissionTo($perm)) {
                        $resellerRole->givePermissionTo($perm);
                    }
                }
            }

            $this->info('  ✓ Store/Sales/Inventory/Reseller/Technician permissions verified');
        } catch (\Throwable $e) {
            $this->warn('  ! Permissions check: ' . $e->getMessage());
        }

        try {
            $freedClients = \DB::table('clients')
                ->where('is_free', true)
                ->where('due_amount', '>', 0)
                ->update(['due_amount' => 0]);
            if ($freedClients > 0) {
                $this->info("  ✓ Cleared due_amount for {$freedClients} free client(s)");
                $changes += $freedClients;
            }

            $freeClientIds = \DB::table('clients')->where('is_free', true)->pluck('id');
            if ($freeClientIds->isNotEmpty()) {
                $orphanedBills = \DB::table('bills')
                    ->whereIn('client_id', $freeClientIds)
                    ->where('status', 'unpaid')
                    ->where('paid_amount', 0)
                    ->delete();
                if ($orphanedBills > 0) {
                    $this->info("  ✓ Removed {$orphanedBills} orphaned bill(s) for free client(s)");
                    $changes += $orphanedBills;
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Free client fixes: ' . $e->getMessage());
        }

        if (!Schema::hasTable('client_schedules')) {
            Schema::create('client_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('action');
                $table->timestamp('scheduled_at');
                $table->string('status')->default('pending');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('mikrotik_profile')->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->text('execution_result')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->timestamps();
                $table->index(['status', 'scheduled_at']);
                $table->index('client_id');
                $table->index('company_id');
            });
            $this->info('  ✓ Created client_schedules table');
            $changes++;
        }

        try {
            $cpChangePackage = \App\Models\SystemSetting::get('client_portal_change_package', '0');
            if ($cpChangePackage !== '1') {
                \App\Models\SystemSetting::set('client_portal_change_package', '1', 'portal');
                $this->info('  ✓ Client portal package change enabled');
                $changes++;
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Portal change package setting: ' . $e->getMessage());
        }

        if (Schema::hasTable('collection_transfers') && !Schema::hasColumn('collection_transfers', 'to_user_id')) {
            Schema::table('collection_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('to_user_id')->nullable()->after('to_account_id');
                $table->foreign('to_user_id')->references('id')->on('users')->nullOnDelete();
            });
            $this->info('  ✓ Added to_user_id column to collection_transfers');
            $changes++;
        }

        if (Schema::hasTable('collection_transfers')) {
            $col = collect(DB::select("SELECT is_nullable FROM information_schema.columns WHERE table_name = 'collection_transfers' AND column_name = 'to_account_id'"))->first();
            if ($col && $col->is_nullable === 'NO') {
                DB::statement('ALTER TABLE collection_transfers DROP CONSTRAINT IF EXISTS collection_transfers_to_account_id_foreign');
                DB::statement('ALTER TABLE collection_transfers ALTER COLUMN to_account_id DROP NOT NULL');
                Schema::table('collection_transfers', function (Blueprint $table) {
                    $table->foreign('to_account_id')->references('id')->on('accounts')->nullOnDelete();
                });
                $this->info('  ✓ Made to_account_id nullable in collection_transfers (FK preserved)');
                $changes++;
            }
        }

        if (Schema::hasTable('accounts') && DB::table('accounts')->count() === 0) {
            $companyId = DB::table('companies')->value('id') ?? 1;
            $now = now();
            DB::table('accounts')->insert([
                ['name' => 'Main Cash', 'type' => 'cash', 'account_number' => null, 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true, 'company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'bKash Account', 'type' => 'mobile_banking', 'account_number' => '01700000000', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true, 'company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Nagad Account', 'type' => 'mobile_banking', 'account_number' => '01700000001', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true, 'company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Bank Account', 'type' => 'bank', 'account_number' => null, 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true, 'company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now],
            ]);
            $this->info('  ✓ Default accounts created (Main Cash, bKash, Nagad, Bank)');
            $changes++;
        }

        // Seed missing default data for all companies (idempotent)
        $this->seedAllCompanyDefaults($changes);

        // Auto-configure Twilio voice call settings from env vars
        $this->setupTwilioVoiceCall($changes);

        // Auto-configure Email system default row
        $this->setupEmailSystem($changes);

        if ($changes === 0) {
            $this->info('All post-setup checks passed. No changes needed.');
        } else {
            $this->info("Post-setup complete: {$changes} change(s) applied.");
        }

        return Command::SUCCESS;
    }

    private function cleanupLegacyRoles(int &$changes): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $legacyRoleNames = [
            'admin',
            'technician',
            'billman',
            'manager',
            'sub_reseller',
            'card-distributor',
            'sub-reseller',
            'Collection Agent',
            'Network Operator',
            'Support Staff',
            'Assistant Admin',
        ];

        $deleted = 0;
        foreach ($legacyRoleNames as $legacyName) {
            $role = \Spatie\Permission\Models\Role::where('name', $legacyName)->first();
            if ($role) {
                DB::table('model_has_roles')->where('role_id', $role->id)->delete();
                DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
                $role->delete();
                $deleted++;
                $this->info("  ✓ Removed legacy role: {$legacyName}");
            }
        }

        if ($deleted > 0) {
            $changes += $deleted;
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    private function ensureTestUsers(int &$changes): void
    {
        if (!Schema::hasTable('companies') || !DB::table('companies')->where('id', 1)->exists()) {
            return;
        }
        $testUsers = [
            [
                'email' => 'technician@isp.com',
                'name' => 'Test Technician',
                'password' => 'Test@123',
                'user_type' => 'technician',
                'role' => 'Field Technician',
                'phone' => '01800000001',
            ],
            [
                'email' => 'billman@isp.com',
                'name' => 'Test Billman',
                'password' => 'Test@123',
                'user_type' => 'billman',
                'role' => 'Billing Officer',
                'phone' => '01800000002',
            ],
            [
                'email' => 'reseller@isp.com',
                'name' => 'Test Reseller',
                'password' => 'Test@123',
                'user_type' => 'reseller',
                'role' => 'Reseller',
                'phone' => '01800000003',
            ],
            [
                'email' => 'manager@isp.com',
                'name' => 'Test Manager',
                'password' => 'Test@123',
                'user_type' => 'manager',
                'role' => 'Branch Manager',
                'phone' => '01800000005',
            ],
            [
                'email' => 'card@isp.com',
                'name' => 'Test Card Distributor',
                'password' => 'Test@123',
                'user_type' => 'card_distributor',
                'role' => 'Card Agent',
                'phone' => '01800000006',
            ],
        ];
        foreach ($testUsers as $data) {
            try {
                $existing = \App\Models\User::where('email', $data['email'])->first();
                if (!$existing) {
                    $user = \App\Models\User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                        'user_type' => $data['user_type'],
                        'phone' => $data['phone'],
                        'is_active' => true,
                        'email_verified_at' => now(),
                        'company_id' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $data['role'], 'guard_name' => 'web']);
                    $user->assignRole($role);
                    $this->info('  ✓ Created ' . $data['email'] . ' (' . $data['password'] . ')');
                    $changes++;
                }
            } catch (\Throwable $e) {
                $this->warn('  ! Test user ' . $data['email'] . ': ' . $e->getMessage());
            }
        }
    }

    private function seedAllCompanyDefaults(int &$changes): void
    {
        try {
            if (!Schema::hasTable('companies') || !Schema::hasTable('payment_methods')) {
                return;
            }

            $companies = DB::table('companies')->orderBy('id')->get();
            $now = now();

            foreach ($companies as $company) {
                $cid = $company->id;
                $seeded = false;

                // Payment Methods — codes uppercase to match app-wide conventions
                $paymentMethods = [
                    ['name' => 'Cash',          'code' => 'CASH',  'description' => 'Cash payment'],
                    ['name' => 'bKash',         'code' => 'BKASH', 'description' => 'bKash mobile banking'],
                    ['name' => 'Nagad',         'code' => 'NAGAD', 'description' => 'Nagad mobile banking'],
                    ['name' => 'Rocket',        'code' => 'ROCKET','description' => 'Rocket mobile banking'],
                    ['name' => 'Bank Transfer', 'code' => 'BANK',  'description' => 'Bank transfer'],
                ];
                foreach ($paymentMethods as $pm) {
                    $exists = DB::table('payment_methods')
                        ->where('company_id', $cid)
                        ->where('code', $pm['code'])
                        ->exists();
                    if (!$exists) {
                        DB::table('payment_methods')->insert(array_merge($pm, ['is_active' => true, 'company_id' => $cid, 'created_at' => $now, 'updated_at' => $now]));
                        $seeded = true;
                    }
                }

                // Accounts
                if (Schema::hasTable('accounts')) {
                    $accounts = [
                        ['name' => 'Main Cash',     'type' => 'cash',           'account_number' => null,          'opening_balance' => 0, 'current_balance' => 0],
                        ['name' => 'bKash Account', 'type' => 'mobile_banking', 'account_number' => '01700000000', 'opening_balance' => 0, 'current_balance' => 0],
                        ['name' => 'Nagad Account', 'type' => 'mobile_banking', 'account_number' => '01700000001', 'opening_balance' => 0, 'current_balance' => 0],
                        ['name' => 'Bank Account',  'type' => 'bank',           'account_number' => null,          'opening_balance' => 0, 'current_balance' => 0],
                    ];
                    foreach ($accounts as $acc) {
                        $q = DB::table('accounts')->where('company_id', $cid)->where('name', $acc['name']);
                        if (Schema::hasColumn('accounts', 'deleted_at')) $q->whereNull('deleted_at');
                        if (!$q->exists()) {
                            DB::table('accounts')->insert(array_merge($acc, ['is_active' => true, 'company_id' => $cid, 'created_at' => $now, 'updated_at' => $now]));
                            $seeded = true;
                        }
                    }
                }

                // Income Heads
                if (Schema::hasTable('income_heads')) {
                    foreach (['Monthly Subscription', 'New Connection Fee', 'Equipment Sale'] as $ih) {
                        if (!DB::table('income_heads')->where('company_id', $cid)->where('name', $ih)->exists()) {
                            DB::table('income_heads')->insert(['name' => $ih, 'is_active' => true, 'company_id' => $cid, 'created_at' => $now, 'updated_at' => $now]);
                            $seeded = true;
                        }
                    }
                }

                // Expense Heads
                if (Schema::hasTable('expense_heads')) {
                    $expenseHeads = [
                        'Salary',
                        'Rent',
                        'Utilities',
                        'Equipment Purchase',
                        'Bandwidth Bill',
                        'Office Supplies',
                        'Fiber & Cable Maintenance',
                        'Tower Maintenance & Rent',
                        'Fuel & Generator',
                        'Vehicle & Transport',
                        'Marketing & Advertising',
                        'Conveyance Allowance',
                        'Technician Service Charge',
                        'Equipment Repair & Maintenance',
                        'Legal & Professional Fee',
                        'Bank Charges & Interest',
                        'Tax & VAT',
                        'Software & License',
                        'Client Installation Cost',
                        'Miscellaneous Expense',
                    ];
                    foreach ($expenseHeads as $eh) {
                        if (!DB::table('expense_heads')->where('company_id', $cid)->where('name', $eh)->exists()) {
                            DB::table('expense_heads')->insert(['name' => $eh, 'is_active' => true, 'company_id' => $cid, 'created_at' => $now, 'updated_at' => $now]);
                            $seeded = true;
                        }
                    }
                }

                // Ticket Categories (with subjects)
                if (Schema::hasTable('ticket_categories')) {
                    $ticketCategories = [
                        ['name' => 'Billing Issue',     'subjects' => ['Bill Not Updated','Wrong Bill Amount','Payment Done but Not Updated','Due Bill Query','Package Charge Issue','Advance Payment Adjustment','Invoice Copy Request','Billing Date Change']],
                        ['name' => 'Internet Issue',    'subjects' => ['No Internet Connection','Internet Frequently Disconnecting','Internet Slow After Evening','High Ping / Latency','Packet Loss','International Bandwidth Issue','Local Server Not Opening','BDIX Not Working']],
                        ['name' => 'New Connection',    'subjects' => ['New Internet Connection Request','Connection Availability Check','New Line Installation','Package Information','Connection Charge Query','Fiber Line Setup Request','ONU Installation']],
                        ['name' => 'Router/ONU Issue',  'subjects' => ['Router Not Working','ONU Offline','Router Configuration Problem','WiFi Not Working','WiFi Password Change','LAN Port Not Working','Router Restart Issue','Router Replacement Request']],
                        ['name' => 'Speed Issue',       'subjects' => ['Speed Lower Than Package','Slow Download Speed','Slow Upload Speed','WiFi Speed Slow','International Speed Slow','BDIX Speed Slow','Speed Test Issue','YouTube Buffering','Facebook Buffering','Online Calling/meeting Issue']],
                        ['name' => 'Technical Support', 'subjects' => ['Remote Support Request','Router Setup Help','Network Configuration Issue','Gaming Ping Issue','CCTV Remote Access Setup']],
                    ];
                    $hasSubjectsCol = Schema::hasColumn('ticket_categories', 'subjects');
                    foreach ($ticketCategories as $tc) {
                        $existing = DB::table('ticket_categories')->where('company_id', $cid)->where('name', $tc['name'])->first();
                        if (!$existing) {
                            $row = ['name' => $tc['name'], 'is_active' => true, 'company_id' => $cid, 'created_at' => $now, 'updated_at' => $now];
                            if ($hasSubjectsCol) {
                                $row['subjects'] = json_encode($tc['subjects']);
                            }
                            DB::table('ticket_categories')->insert($row);
                            $seeded = true;
                        } elseif ($hasSubjectsCol && empty($existing->subjects)) {
                            DB::table('ticket_categories')->where('id', $existing->id)->update(['subjects' => json_encode($tc['subjects']), 'updated_at' => $now]);
                        }
                    }
                }

                // SMS Templates — seed built-in defaults for all companies
                if (Schema::hasTable('sms_templates')) {
                    $builtinTemplates = [
                        ['name' => 'Bill Generated',      'type' => 'bill_generated',      'code' => 'BILL_GEN',   'content' => 'Dear {client_name}, your bill of {amount} BDT has been generated for {month}. Due date: {due_date}. - {company_name}'],
                        ['name' => 'Payment Received',    'type' => 'payment_received',    'code' => 'PAY_RECV',   'content' => 'Dear {client_name}, we received your payment of {amount} BDT on {date}. Thank you! - {company_name}'],
                        ['name' => 'Account Activated',   'type' => 'account_activated',   'code' => 'ACC_ACT',    'content' => 'Dear {client_name}, your account has been activated. Enjoy our service! - {company_name}'],
                        ['name' => 'Account Deactivated', 'type' => 'account_deactivated', 'code' => 'ACC_DEACT',  'content' => 'Dear {client_name}, your account has been deactivated due to non-payment. Please pay your dues to restore service. - {company_name}'],
                        ['name' => 'Due Reminder',        'type' => 'due_reminder',        'code' => 'DUE_REM',    'content' => 'Dear {client_name}, you have a pending due of {amount} BDT. Please pay to avoid service interruption. - {company_name}'],
                    ];
                    foreach ($builtinTemplates as $tpl) {
                        if (!DB::table('sms_templates')->where('company_id', $cid)->where('code', $tpl['code'])->exists()) {
                            DB::table('sms_templates')->insert(array_merge($tpl, [
                                'is_active'  => true,
                                'company_id' => $cid,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]));
                            $seeded = true;
                        }
                    }
                }

                // System Settings — seed general defaults for each company
                if (Schema::hasTable('system_settings')) {
                    $systemDefaults = [
                        ['group' => 'general', 'key' => 'auto_disable_days',    'value' => '7'],
                        ['group' => 'general', 'key' => 'bill_generation_day',  'value' => '1'],
                        ['group' => 'general', 'key' => 'due_days',             'value' => '15'],
                        ['group' => 'general', 'key' => 'sms_gateway',          'value' => 'none'],
                        ['group' => 'company', 'key' => 'company_name',         'value' => ''],
                        ['group' => 'company', 'key' => 'company_phone',        'value' => ''],
                        ['group' => 'company', 'key' => 'company_email',        'value' => ''],
                        ['group' => 'company', 'key' => 'company_address',      'value' => ''],
                        ['group' => 'portal',  'key' => 'client_portal_change_package', 'value' => '0'],
                    ];
                    foreach ($systemDefaults as $row) {
                        if (!DB::table('system_settings')->where('company_id', $cid)->where('key', $row['key'])->exists()) {
                            DB::table('system_settings')->insert(array_merge($row, ['company_id' => $cid, 'created_at' => $now, 'updated_at' => $now]));
                            $seeded = true;
                        }
                    }
                }

                // Automation Settings — seed default row if missing
                if (Schema::hasTable('automation_settings')) {
                    if (!DB::table('automation_settings')->where('company_id', $cid)->exists()) {
                        $minDue = Schema::hasColumn('automation_settings', 'min_due_for_inactive') ? 0 : null;
                        $row = ['bill_generate_time' => '09:00', 'disconnect_time' => '23:59', 'company_id' => $cid, 'created_at' => $now, 'updated_at' => $now];
                        if ($minDue !== null) $row['min_due_for_inactive'] = $minDue;
                        DB::table('automation_settings')->insert($row);
                        $seeded = true;
                    }
                }

                // Departments + Designations (department-linked)
                if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'company_id')
                    && Schema::hasTable('designations')) {

                    $companyDeptDesignations = [
                        'Technical' => [
                            'NOC Engineer', 'Network Engineer', 'NOC Technician',
                            'Senior Technical Support Engineer', 'Technical Support Engineer', 'Support Technician',
                        ],
                        'Sales' => [
                            'Sales Manager', 'Sales Executive', 'Marketing Executive',
                        ],
                        'Accounts' => [
                            'Accounts Manager', 'Accounts Executive', 'Collection Executive',
                        ],
                        'Administration' => [
                            'Operations Manager', 'HR Manager', 'Administrative Officer',
                            'Chief Executive Officer (CEO)', 'Chief Technology Officer (CTO)',
                        ],
                    ];

                    foreach ($companyDeptDesignations as $deptName => $designations) {
                        $dept = DB::table('departments')
                            ->where('company_id', $cid)->where('name', $deptName)->first();
                        if (!$dept) {
                            $deptId = DB::table('departments')->insertGetId([
                                'name' => $deptName, 'is_active' => true,
                                'company_id' => $cid, 'created_at' => $now, 'updated_at' => $now,
                            ]);
                            $seeded = true;
                        } else {
                            $deptId = $dept->id;
                        }

                        foreach ($designations as $desigName) {
                            if (!DB::table('designations')
                                ->where('department_id', $deptId)->where('name', $desigName)->exists()) {
                                DB::table('designations')->insert([
                                    'name' => $desigName, 'department_id' => $deptId,
                                    'company_id' => $cid, 'is_active' => true,
                                    'created_at' => $now, 'updated_at' => $now,
                                ]);
                                $seeded = true;
                            }
                        }
                    }
                }

                if ($seeded) {
                    $this->info("  ✓ Seeded missing default data for Company ID {$cid} ({$company->name})");
                    $changes++;
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  ! Company defaults seeding: ' . $e->getMessage());
        }
    }

    private function seedBangladeshDistricts(int &$changes): void
    {
        if (!Schema::hasTable('districts')) {
            return;
        }

        // Always ensure all active districts are marked as configured (run even if already seeded)
        if (Schema::hasColumn('districts', 'is_configured')) {
            $fixed = DB::table('districts')->where('is_active', true)->where('is_configured', false)->update(['is_configured' => true]);
            if ($fixed > 0) {
                $this->info("  ✓ Marked {$fixed} district(s) as configured");
                $changes++;
            }
        }

        $requiredDivisions = ['Barishal', 'Chattogram', 'Dhaka', 'Khulna', 'Mymensingh', 'Rajshahi', 'Rangpur', 'Sylhet'];
        $existingDivisions = DB::table('districts')
            ->whereNotNull('division')
            ->where('division', '!=', '')
            ->distinct()
            ->pluck('division')
            ->toArray();

        if (count(array_intersect($requiredDivisions, $existingDivisions)) === 8) {
            return;
        }

        $districtsByDivision = [
            'Barishal' => ['Barguna', 'Barishal', 'Bhola', 'Jhalokati', 'Patuakhali', 'Pirojpur'],
            'Chattogram' => ['Bandarban', 'Brahmanbaria', 'Chandpur', 'Chattogram', 'Comilla', "Cox's Bazar", 'Feni', 'Khagrachhari', 'Lakshmipur', 'Noakhali', 'Rangamati'],
            'Dhaka' => ['Dhaka', 'Faridpur', 'Gazipur', 'Gopalganj', 'Kishoreganj', 'Madaripur', 'Manikganj', 'Munshiganj', 'Narayanganj', 'Narsingdi', 'Rajbari', 'Shariatpur', 'Tangail'],
            'Khulna' => ['Bagerhat', 'Chuadanga', 'Jessore', 'Jhenaidah', 'Khulna', 'Kushtia', 'Magura', 'Meherpur', 'Narail', 'Satkhira'],
            'Mymensingh' => ['Jamalpur', 'Mymensingh', 'Netrokona', 'Sherpur'],
            'Rajshahi' => ['Bogura', 'Chapainawabganj', 'Joypurhat', 'Naogaon', 'Natore', 'Nawabganj', 'Pabna', 'Rajshahi', 'Sirajganj'],
            'Rangpur' => ['Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat', 'Nilphamari', 'Panchagarh', 'Rangpur', 'Thakurgaon'],
            'Sylhet' => ['Habiganj', 'Moulvibazar', 'Sunamganj', 'Sylhet'],
        ];

        $inserted = 0;
        $now = now();

        foreach ($districtsByDivision as $division => $districts) {
            foreach ($districts as $districtName) {
                $exists = DB::table('districts')
                    ->where('name', $districtName)
                    ->exists();

                if (!$exists) {
                    DB::table('districts')->insert([
                        'name' => $districtName,
                        'division' => $division,
                        'is_active' => true,
                        'is_configured' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $inserted++;
                }
            }
        }

        if ($inserted > 0) {
            $this->info("  ✓ Seeded {$inserted} Bangladesh districts across 8 divisions");
            $changes++;
        }

    }

    private function seedDepartmentsAndDesignations(int &$changes): void
    {
        if (!Schema::hasTable('departments') || !Schema::hasTable('designations')) {
            return;
        }

        $departmentsWithDesignations = [
            'Network Operations Department (NOC)' => [
                'NOC Engineer',
                'Senior Network Engineer',
                'Network Engineer',
                'NOC Technician',
            ],
            'Technical Support Department' => [
                'Senior Technical Support Engineer',
                'Technical Support Engineer',
                'Support Technician',
            ],
            'Customer Support / Customer Service' => [
                'Customer Service Executive',
                'Call Center Executive',
            ],
            'Sales & Marketing Department' => [
                'Sales Manager',
                'Sales Executive',
                'Marketing Executive',
            ],
            'Billing & Accounts Department' => [
                'Accounts Manager',
                'Accounts Executive',
                'Collection Executive',
            ],
            'System / IT Department' => [
                'System Administrator',
                'DevOps Engineer',
                'Software Developer',
            ],
            'Management / Administration' => [
                'Chief Executive Officer (CEO)',
                'Chief Technology Officer (CTO)',
                'Operations Manager',
                'HR Manager',
                'Administrative Officer',
            ],
        ];

        $now = now();
        $deptInserted = 0;
        $desigInserted = 0;

        foreach ($departmentsWithDesignations as $deptName => $designations) {
            $dept = DB::table('departments')->where('name', $deptName)->first();
            if (!$dept) {
                $deptId = DB::table('departments')->insertGetId([
                    'name' => $deptName,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $deptInserted++;
            } else {
                $deptId = $dept->id;
            }

            foreach ($designations as $desigName) {
                $exists = DB::table('designations')
                    ->where('name', $desigName)
                    ->where('department_id', $deptId)
                    ->exists();

                if (!$exists) {
                    DB::table('designations')->insert([
                        'name' => $desigName,
                        'department_id' => $deptId,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $desigInserted++;
                }
            }
        }

        if ($deptInserted > 0 || $desigInserted > 0) {
            $this->info("  ✓ Seeded {$deptInserted} department(s) and {$desigInserted} designation(s)");
            $changes++;
        }
    }

    protected function setupEmailSystem(int &$changes): void
    {
        if (!Schema::hasTable('email_settings')) {
            return;
        }

        $existing = DB::table('email_settings')->first();
        if (!$existing) {
            DB::table('email_settings')->insert([
                'mailer'                       => 'smtp',
                'host'                         => 'smtp.gmail.com',
                'port'                         => 587,
                'username'                     => '',
                'password'                     => '',
                'encryption'                   => 'tls',
                'from_address'                 => 'noreply@isp.com',
                'from_name'                    => 'SOFTYNEX',
                'is_active'                    => false,
                'auto_bill_reminder'           => false,
                'auto_expiry_warning'          => false,
                'auto_payment_confirmation'    => true,
                'bill_reminder_days_before'    => 3,
                'expiry_warning_days_before'   => 3,
                'created_at'                   => now(),
                'updated_at'                   => now(),
            ]);
            $this->info('  ✓ Created default email_settings row (configure SMTP to activate)');
            $changes++;
        }
    }

    protected function setupTwilioVoiceCall(int &$changes): void
    {
        if (!Schema::hasTable('voice_call_settings')) {
            return;
        }

        $sid    = env('TWILIO_ACCOUNT_SID');
        $token  = env('TWILIO_AUTH_TOKEN');
        $number = env('TWILIO_PHONE_NUMBER');

        if (empty($sid) || empty($token) || empty($number)) {
            return;
        }

        $existing = DB::table('voice_call_settings')->first();

        if (!$existing) {
            DB::table('voice_call_settings')->insert([
                'provider'            => 'twilio',
                'from_number'         => $number,
                'credentials'         => json_encode([
                    'account_sid' => $sid,
                    'auth_token'  => $token,
                ]),
                'is_active'           => true,
                'auto_bill_reminder'  => true,
                'auto_expiry_warning' => true,
                'reminder_days_before'=> 3,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            $this->info('  ✓ Auto-configured Twilio voice call settings from environment');
            $changes++;
        } elseif (
            ($existing->provider !== 'twilio') ||
            (empty($existing->credentials)) ||
            ($existing->from_number !== $number)
        ) {
            DB::table('voice_call_settings')->where('id', $existing->id)->update([
                'provider'    => 'twilio',
                'from_number' => $number,
                'credentials' => json_encode([
                    'account_sid' => $sid,
                    'auth_token'  => $token,
                ]),
                'updated_at'  => now(),
            ]);
            $this->info('  ✓ Updated Twilio credentials in voice call settings');
            $changes++;
        }
    }
}
