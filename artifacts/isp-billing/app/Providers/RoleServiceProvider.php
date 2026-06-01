<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class RoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (!$this->shouldSeed()) {
            return;
        }

        $this->seedEssentialData();
    }

    protected function shouldSeed(): bool
    {
        try {
            if (!Schema::hasTable('cache') || !Schema::hasTable('roles')) {
                return false;
            }
            
            if (!Cache::has('essential_data_seeded')) {
                return true;
            }

            $roleCount = DB::table('roles')->count();
            if ($roleCount < 5) {
                Cache::forget('essential_data_seeded');
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    protected function seedEssentialData(): void
    {
        try {
            $this->ensureRolesExist();
            $this->ensurePaymentMethodsExist();
            $this->ensureTicketCategoriesExist();
            $this->ensureSystemSettingsExist();
            $this->ensureMikroTikSimulatorExists();

            Cache::put('essential_data_seeded', true, now()->addHours(24));
        } catch (\Exception $e) {
            // Silently fail during boot if database isn't ready
        }
    }

    protected function ensureRolesExist(): void
    {
        try {
            if (!Schema::hasTable('roles')) {
                return;
            }

            $roles = [
                'Super Admin',
                'Administrator',
                'Admin Support',
                'Assistant Manager',
                'Branch Manager',
                'Supervisor',
                'Field Technician',
                'Billing Officer',
                'Collection Officer',
                'Network Engineer',
                'Support Executive',
                'Card Agent',
                'Reseller',
                'Sub Reseller',
                'client',
            ];

            foreach ($roles as $roleName) {
                Role::firstOrCreate(
                    ['name' => $roleName, 'guard_name' => 'web']
                );
            }
            
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Exception $e) {
            // Silently fail during boot if database isn't ready
        }
    }

    protected function ensurePaymentMethodsExist(): void
    {
        try {
            if (!Schema::hasTable('payment_methods')) {
                return;
            }

            $paymentMethods = [
                ['name' => 'Cash', 'code' => 'CASH', 'description' => 'Cash payment', 'is_active' => true],
                ['name' => 'bKash', 'code' => 'BKASH', 'description' => 'bKash mobile payment', 'is_active' => true],
                ['name' => 'Nagad', 'code' => 'NAGAD', 'description' => 'Nagad mobile payment', 'is_active' => true],
                ['name' => 'Rocket', 'code' => 'ROCKET', 'description' => 'Rocket mobile payment', 'is_active' => true],
                ['name' => 'Bank Transfer', 'code' => 'BANK', 'description' => 'Bank transfer', 'is_active' => true],
            ];

            foreach ($paymentMethods as $method) {
                DB::table('payment_methods')->updateOrInsert(
                    ['code' => $method['code']],
                    array_merge($method, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        } catch (\Exception $e) {
            // Silently fail during boot if database isn't ready
        }
    }

    protected function ensureTicketCategoriesExist(): void
    {
        try {
            if (!Schema::hasTable('ticket_categories')) {
                return;
            }

            $categories = [
                ['name' => 'Internet Issue', 'description' => '', 'priority' => 0, 'is_active' => true],
                ['name' => 'Billing Issue', 'description' => '', 'priority' => 0, 'is_active' => true],
                ['name' => 'New Connection', 'description' => '', 'priority' => 0, 'is_active' => true],
                ['name' => 'Technical Support', 'description' => '', 'priority' => 0, 'is_active' => true],
                ['name' => 'Speed Issue', 'description' => '', 'priority' => 0, 'is_active' => true],
                ['name' => 'Router/ONU Issue', 'description' => '', 'priority' => 0, 'is_active' => true],
            ];

            foreach ($categories as $category) {
                DB::table('ticket_categories')->updateOrInsert(
                    ['name' => $category['name']],
                    array_merge($category, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        } catch (\Exception $e) {
            // Silently fail during boot if database isn't ready
        }
    }

    protected function ensureSystemSettingsExist(): void
    {
        try {
            if (!Schema::hasTable('system_settings')) {
                return;
            }

            // Seed defaults scoped to company_id=1 (the primary/first company)
            // Uses composite key (key, company_id) matching the schema constraint
            $settings = [
                ['key' => 'auto_disable_days',   'value' => '7',       'group' => 'general'],
                ['key' => 'bill_generation_day',  'value' => '1',       'group' => 'general'],
                ['key' => 'due_days',             'value' => '15',      'group' => 'general'],
                ['key' => 'sms_gateway',          'value' => 'none',    'group' => 'general'],
                ['key' => 'company_name',         'value' => '',        'group' => 'company'],
                ['key' => 'company_phone',        'value' => '',        'group' => 'company'],
                ['key' => 'company_email',        'value' => '',        'group' => 'company'],
                ['key' => 'company_address',      'value' => '',        'group' => 'company'],
                ['key' => 'client_portal_change_package', 'value' => '0', 'group' => 'portal'],
            ];

            foreach ($settings as $setting) {
                DB::table('system_settings')->updateOrInsert(
                    ['key' => $setting['key'], 'company_id' => 1],
                    array_merge($setting, ['company_id' => 1, 'created_at' => now(), 'updated_at' => now()])
                );
            }
        } catch (\Exception $e) {
            // Silently fail during boot if database isn't ready
        }
    }

    protected function ensureMikroTikSimulatorExists(): void
    {
        try {
            if (!Schema::hasTable('mikrotik_devices')) {
                return;
            }

            DB::table('mikrotik_devices')->updateOrInsert(
                ['name' => 'Simulator'],
                [
                    'name' => 'Simulator',
                    'host' => '127.0.0.1',
                    'username' => 'admin',
                    'password' => '',
                    'port' => 8728,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            // Silently fail during boot if database isn't ready
        }
    }
}
