<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Reseller;
use App\Models\Branch;
use App\Models\ManagerAssignment;
use App\Models\CardDistributor;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Package;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        TenantContext::setSuperAdminMode(true);

        $this->createTechnicianUser();
        $this->createBillmanUser();
        $this->createResellerUser();
        $this->createManagerUser();
        $this->createCardDistributorUser();
        $this->createClientUser();

        $this->command->info('All test panel users created successfully!');
    }

    private function createTechnicianUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'technician@isp.com'],
            [
                'name' => 'Test Technician',
                'password' => Hash::make('Test@123'),
                'phone' => '01800000001',
                'user_type' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
                'company_id' => 1,
            ]
        );

        Role::firstOrCreate(['name' => 'Field Technician', 'guard_name' => 'web']);
        if (!$user->hasRole('Field Technician')) {
            $user->assignRole('Field Technician');
        }

        $department = Department::first();
        $designation = Designation::first();

        $allZoneIds = Zone::withoutGlobalScopes()->where('company_id', 1)->pluck('id')->toArray();
        if (empty($allZoneIds)) {
            $allZoneIds = Zone::withoutGlobalScopes()->pluck('id')->toArray();
        }
        if ($department && $designation) {
            $employee = Employee::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'department_id' => $department->id,
                    'designation_id' => $designation->id,
                    'joining_date' => Carbon::now()->subMonths(12),
                    'basic_salary' => 20000,
                    'status' => 'active',
                    'company_id' => 1,
                    'zone_ids' => $allZoneIds,
                ]
            );
        }

        $this->command->info('  Technician: technician@isp.com / Test@123');
    }

    private function createBillmanUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'billman@isp.com'],
            [
                'name' => 'Test Billman',
                'password' => Hash::make('Test@123'),
                'phone' => '01800000002',
                'user_type' => 'billman',
                'is_active' => true,
                'email_verified_at' => now(),
                'company_id' => 1,
            ]
        );

        Role::firstOrCreate(['name' => 'Billing Officer', 'guard_name' => 'web']);
        if (!$user->hasRole('Billing Officer')) {
            $user->assignRole('Billing Officer');
        }

        $this->command->info('  Billman: billman@isp.com / Test@123');
    }

    private function createResellerUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'reseller@isp.com'],
            [
                'name' => 'Test Reseller',
                'password' => Hash::make('Test@123'),
                'phone' => '01800000003',
                'user_type' => 'reseller',
                'is_active' => true,
                'email_verified_at' => now(),
                'company_id' => 1,
            ]
        );

        Role::firstOrCreate(['name' => 'Reseller', 'guard_name' => 'web']);
        if (!$user->hasRole('Reseller')) {
            $user->assignRole('Reseller');
        }

        Reseller::firstOrCreate(
            ['user_id' => $user->id],
            [
                'reseller_id' => 'RSL-0001',
                'name' => $user->name,
                'company_name' => 'Test Reseller Co.',
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => 'Dhaka, Bangladesh',
                'type' => 'bandwidth',
                'commission_rate' => 10,
                'wallet_balance' => 0,
                'billing_type' => 'postpaid',
                'credit_limit' => 50000,
                'status' => 'active',
            ]
        );

        $this->command->info('  Reseller: reseller@isp.com / Test@123');
    }

    private function createManagerUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'manager@isp.com'],
            [
                'name' => 'Test Manager',
                'password' => Hash::make('Test@123'),
                'phone' => '01800000005',
                'user_type' => 'manager',
                'is_active' => true,
                'email_verified_at' => now(),
                'company_id' => 1,
            ]
        );

        Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
        if (!$user->hasRole('Branch Manager')) {
            $user->assignRole('Branch Manager');
        }

        $branch = Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            [
                'code' => 'MAIN',
                'address' => 'Dhaka, Bangladesh',
                'phone' => '01800000000',
                'is_active' => true,
                'is_main' => true,
                'manager_id' => $user->id,
            ]
        );

        ManagerAssignment::firstOrCreate(
            ['user_id' => $user->id],
            [
                'branch_id' => $branch->id,
                'can_manage_billing' => true,
                'can_manage_clients' => true,
                'can_manage_staff' => true,
                'can_view_reports' => true,
                'can_manage_resellers' => true,
                'is_active' => true,
            ]
        );

        $this->command->info('  Manager: manager@isp.com / Test@123');
    }

    private function createCardDistributorUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'card@isp.com'],
            [
                'name' => 'Test Card Distributor',
                'password' => Hash::make('Test@123'),
                'phone' => '01800000006',
                'user_type' => 'card_distributor',
                'is_active' => true,
                'email_verified_at' => now(),
                'company_id' => 1,
            ]
        );

        Role::firstOrCreate(['name' => 'Card Agent', 'guard_name' => 'web']);
        if (!$user->hasRole('Card Agent')) {
            $user->assignRole('Card Agent');
        }

        CardDistributor::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'address' => 'Dhaka, Bangladesh',
                'commission_rate' => 5,
                'total_sales' => 0,
                'total_commission' => 0,
                'balance' => 0,
                'is_active' => true,
            ]
        );

        $this->command->info('  Card Distributor: card@isp.com / Test@123');
    }

    private function createClientUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'client@isp.com'],
            [
                'name' => 'Test Client',
                'password' => Hash::make('Test@123'),
                'phone' => '01800000007',
                'user_type' => 'client',
                'is_active' => true,
                'email_verified_at' => now(),
                'company_id' => 1,
            ]
        );

        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        if (!$user->hasRole('client')) {
            $user->assignRole('client');
        }

        $zone = Zone::first();
        $area = Area::first();
        $package = Package::first();

        if ($zone && $package) {
            $existingClient = Client::where('user_id', $user->id)->first();
            if (!$existingClient) {
                $orphanedClient = Client::whereNull('user_id')->first();
                if ($orphanedClient) {
                    $orphanedClient->update(['user_id' => $user->id]);
                } else {
                    Client::create([
                        'user_id' => $user->id,
                        'client_id' => 'CLI-0001',
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'address' => 'House 1, Road 1, Dhaka',
                        'zone_id' => $zone->id,
                        'area_id' => $area ? $area->id : null,
                        'package_id' => $package->id,
                        'username' => 'testclient',
                        'pppoe_password' => 'test123',
                        'connection_type' => 'pppoe',
                        'connection_date' => Carbon::now()->subMonths(6),
                        'expire_date' => Carbon::now()->addDays(30),
                        'billing_date' => 1,
                        'monthly_fee' => $package->price ?? 500,
                        'status' => 'active',
                        'connection_status' => 'online',
                        'company_id' => 1,
                    ]);
                }
            }
        }

        $this->command->info('  Client: client@isp.com / Test@123');
    }
}
