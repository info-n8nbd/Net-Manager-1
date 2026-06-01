<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\CompanyProfile;
use App\Models\Account;
use App\Models\IncomeHead;
use App\Models\ExpenseHead;
use App\Models\Department;
use App\Models\Designation;
use App\Models\TicketCategory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Services\TenantContext;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        TenantContext::setSuperAdminMode(true);
        $this->call([
            RoleSeeder::class,
            SuperAdminSeeder::class,
            PermissionSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@isp.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Test@123'),
                'phone' => '01700000000',
                'user_type' => 'admin',
                'is_active' => true,
                'company_id' => 1,
            ]
        );

        if (!$admin->hasRole('admin') && !$admin->hasRole('Super Admin')) {
            $admin->assignRole('admin');
        }


        $packages = [
            ['name' => 'Basic Home', 'code' => 'HOME-5', 'price' => 500, 'bandwidth' => '5/5 Mbps', 'download_speed' => 5, 'upload_speed' => 5, 'connection_type' => 'PPPoE'],
            ['name' => 'Standard Home', 'code' => 'HOME-10', 'price' => 800, 'bandwidth' => '10/10 Mbps', 'download_speed' => 10, 'upload_speed' => 10, 'connection_type' => 'PPPoE'],
            ['name' => 'Premium Home', 'code' => 'HOME-20', 'price' => 1200, 'bandwidth' => '20/20 Mbps', 'download_speed' => 20, 'upload_speed' => 20, 'connection_type' => 'PPPoE'],
            ['name' => 'Ultra Home', 'code' => 'HOME-50', 'price' => 2000, 'bandwidth' => '50/50 Mbps', 'download_speed' => 50, 'upload_speed' => 50, 'connection_type' => 'PPPoE'],
            ['name' => 'Business Basic', 'code' => 'BIZ-10', 'price' => 2500, 'bandwidth' => '10/10 Mbps', 'download_speed' => 10, 'upload_speed' => 10, 'connection_type' => 'Static IP'],
            ['name' => 'Business Pro', 'code' => 'BIZ-50', 'price' => 5000, 'bandwidth' => '50/50 Mbps', 'download_speed' => 50, 'upload_speed' => 50, 'connection_type' => 'Static IP'],
        ];
        foreach ($packages as $package) {
            $package['company_id'] = 1;
            Package::firstOrCreate(['code' => $package['code']], $package);
        }

        $paymentMethods = [
            ['name' => 'Cash', 'code' => 'CASH', 'description' => 'Cash payment'],
            ['name' => 'bKash', 'code' => 'BKASH', 'description' => 'bKash mobile payment'],
            ['name' => 'Nagad', 'code' => 'NAGAD', 'description' => 'Nagad mobile payment'],
            ['name' => 'Rocket', 'code' => 'ROCKET', 'description' => 'Rocket mobile payment'],
            ['name' => 'Bank Transfer', 'code' => 'BANK', 'description' => 'Bank transfer'],
        ];
        foreach ($paymentMethods as $method) {
            $method['company_id'] = 1;
            PaymentMethod::firstOrCreate(['code' => $method['code']], $method);
        }

        CompanyProfile::firstOrCreate(
            ['name' => 'Your ISP Company'],
            [
                'email' => 'info@yourisp.com',
                'phone' => '01700000000',
                'address' => 'Dhaka, Bangladesh',
                'currency_symbol' => '৳',
                'currency_code' => 'BDT',
                'company_id' => 1,
            ]
        );

        Account::firstOrCreate(['name' => 'Main Cash'], ['type' => 'cash', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true, 'company_id' => 1]);
        Account::firstOrCreate(['name' => 'bKash Account'], ['type' => 'mobile_banking', 'account_number' => '01700000000', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true, 'company_id' => 1]);

        IncomeHead::firstOrCreate(['name' => 'Monthly Subscription'], ['is_active' => true, 'company_id' => 1]);
        IncomeHead::firstOrCreate(['name' => 'New Connection Fee'], ['is_active' => true, 'company_id' => 1]);
        IncomeHead::firstOrCreate(['name' => 'Equipment Sale'], ['is_active' => true, 'company_id' => 1]);

        ExpenseHead::firstOrCreate(['name' => 'Salary'], ['is_active' => true, 'company_id' => 1]);
        ExpenseHead::firstOrCreate(['name' => 'Rent'], ['is_active' => true, 'company_id' => 1]);
        ExpenseHead::firstOrCreate(['name' => 'Utilities'], ['is_active' => true, 'company_id' => 1]);
        ExpenseHead::firstOrCreate(['name' => 'Equipment Purchase'], ['is_active' => true, 'company_id' => 1]);

        Department::firstOrCreate(['name' => 'Technical'], ['is_active' => true, 'company_id' => 1]);
        Department::firstOrCreate(['name' => 'Sales'], ['is_active' => true, 'company_id' => 1]);
        Department::firstOrCreate(['name' => 'Accounts'], ['is_active' => true, 'company_id' => 1]);
        Department::firstOrCreate(['name' => 'Administration'], ['is_active' => true, 'company_id' => 1]);

        Designation::firstOrCreate(['name' => 'Manager'], ['is_active' => true, 'company_id' => 1]);
        Designation::firstOrCreate(['name' => 'Technician'], ['is_active' => true, 'company_id' => 1]);
        Designation::firstOrCreate(['name' => 'Sales Executive'], ['is_active' => true, 'company_id' => 1]);
        Designation::firstOrCreate(['name' => 'Accountant'], ['is_active' => true, 'company_id' => 1]);

        $ticketCategoryData = [
            ['name' => 'Billing Issue',     'subjects' => ['Bill Not Updated','Wrong Bill Amount','Payment Done but Not Updated','Due Bill Query','Package Charge Issue','Advance Payment Adjustment','Invoice Copy Request','Billing Date Change']],
            ['name' => 'Internet Issue',    'subjects' => ['No Internet Connection','Internet Frequently Disconnecting','Internet Slow After Evening','High Ping / Latency','Packet Loss','International Bandwidth Issue','Local Server Not Opening','BDIX Not Working']],
            ['name' => 'New Connection',    'subjects' => ['New Internet Connection Request','Connection Availability Check','New Line Installation','Package Information','Connection Charge Query','Fiber Line Setup Request','ONU Installation']],
            ['name' => 'Router/ONU Issue',  'subjects' => ['Router Not Working','ONU Offline','Router Configuration Problem','WiFi Not Working','WiFi Password Change','LAN Port Not Working','Router Restart Issue','Router Replacement Request']],
            ['name' => 'Speed Issue',       'subjects' => ['Speed Lower Than Package','Slow Download Speed','Slow Upload Speed','WiFi Speed Slow','International Speed Slow','BDIX Speed Slow','Speed Test Issue','YouTube Buffering','Facebook Buffering','Online Calling/meeting Issue']],
            ['name' => 'Technical Support', 'subjects' => ['Remote Support Request','Router Setup Help','Network Configuration Issue','Gaming Ping Issue','CCTV Remote Access Setup']],
        ];
        foreach ($ticketCategoryData as $tc) {
            $existing = TicketCategory::where('name', $tc['name'])->where('company_id', 1)->first();
            if (!$existing) {
                TicketCategory::create(['name' => $tc['name'], 'subjects' => $tc['subjects'], 'is_active' => true, 'company_id' => 1]);
            } elseif (empty($existing->subjects)) {
                $existing->update(['subjects' => $tc['subjects']]);
            }
        }

        $this->call([
            TestUsersSeeder::class,
            BangladeshDistrictSeeder::class,
            DepartmentDesignationSeeder::class,
            DemoClientsSeeder::class,
        ]);
    }
}
