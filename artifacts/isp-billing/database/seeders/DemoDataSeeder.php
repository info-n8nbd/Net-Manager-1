<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\TenantContext;
use App\Models\User;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Employee;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Reseller;
use App\Models\Product;
use App\Models\MikrotikDevice;
use App\Models\Attendance;
use App\Models\Zone;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\Account;
use App\Models\IncomeHead;
use App\Models\ExpenseHead;
use App\Models\Department;
use App\Models\Designation;
use App\Models\TicketCategory;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    private function getEmployeeUserId(array $employees): ?int
    {
        if (empty($employees)) {
            return null;
        }
        $emp = $employees[array_rand($employees)];
        if (is_object($emp)) {
            return $emp->user_id ?? null;
        }
        return $emp['user_id'] ?? null;
    }

    public function run(): void
    {
        TenantContext::setSuperAdminMode(true);
        $zones = Zone::all();
        $packages = Package::all();
        $paymentMethods = PaymentMethod::all();
        $departments = Department::all();
        $designations = Designation::all();
        $categories = TicketCategory::all();
        $accounts = Account::all();
        $expenseHeads = ExpenseHead::all();
        $incomeHeads = IncomeHead::all();

        $employees = Employee::all()->toArray();
        if (empty($employees)) {
            $employeeData = [
                ['name' => 'Rahim Ahmed', 'email' => 'rahim@isp.com', 'phone' => '01711111111', 'salary' => 25000],
                ['name' => 'Karim Hossain', 'email' => 'karim@isp.com', 'phone' => '01722222222', 'salary' => 20000],
                ['name' => 'Jamal Uddin', 'email' => 'jamal@isp.com', 'phone' => '01733333333', 'salary' => 18000],
                ['name' => 'Faruk Khan', 'email' => 'faruk@isp.com', 'phone' => '01744444444', 'salary' => 22000],
                ['name' => 'Nasir Alam', 'email' => 'nasir@isp.com', 'phone' => '01755555555', 'salary' => 15000],
            ];

            foreach ($employeeData as $i => $data) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => Hash::make('password'),
                        'phone' => $data['phone'],
                        'user_type' => 'employee',
                        'is_active' => true,
                    ]
                );

                $employees[] = Employee::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'department_id' => $departments->random()->id,
                        'designation_id' => $designations->random()->id,
                        'joining_date' => Carbon::now()->subMonths(rand(6, 24)),
                        'basic_salary' => $data['salary'],
                        'status' => 'active',
                    ]
                );
            }
        }

        $clientNames = [
            'Md. Abdul Karim', 'Fatema Begum', 'Mohammad Rafiq', 'Salma Akhter', 'Hasibur Rahman',
            'Aminul Islam', 'Rina Parvin', 'Shafiqul Islam', 'Mahmuda Khatun', 'Zakir Hossain',
            'Nusrat Jahan', 'Abul Kalam', 'Shirin Sultana', 'Monirul Islam', 'Rashida Begum',
            'Habibur Rahman', 'Taslima Akter', 'Nazrul Islam', 'Kamrunnahar', 'Shahidul Islam',
            'Sultana Razia', 'Mizanur Rahman', 'Bilkis Begum', 'Anisur Rahman', 'Rehana Akter',
            'Farid Ahmed', 'Nasima Akter', 'Golam Mostafa', 'Roksana Begum', 'Delwar Hossain',
            'Jahanara Begum', 'Nurul Amin', 'Sabina Yasmin', 'Enamul Haque', 'Morsheda Begum',
            'Harunur Rashid', 'Kulsum Akter', 'Rafiqul Islam', 'Hasina Begum', 'Alamgir Hossain'
        ];

        $clients = [];
        foreach ($clientNames as $i => $name) {
            $zone = $zones->random();
            $package = $packages->random();
            $status = $i < 35 ? 'active' : ['inactive', 'inactive', 'pending'][rand(0, 2)];
            $connectionDate = Carbon::now()->subMonths(rand(1, 18));
            $dueAmount = rand(0, 5) > 3 ? $package->price * rand(1, 3) : 0;

            $clientUser = User::firstOrCreate(
                ['email' => 'client' . ($i + 1) . '@example.com'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'phone' => '018' . str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                    'user_type' => 'client',
                    'is_active' => true,
                ]
            );

            $clients[] = Client::firstOrCreate(
                ['user_id' => $clientUser->id],
                [
                    'client_id' => 'ISP-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'name' => $name,
                'email' => $clientUser->email,
                'phone' => $clientUser->phone,
                'address' => 'House ' . rand(1, 500) . ', Road ' . rand(1, 50) . ', ' . $zone->name,
                'zone_id' => $zone->id,
                'package_id' => $package->id,
                'username' => 'pppoe_user_' . ($i + 1),
                'pppoe_password' => 'pass' . rand(1000, 9999),
                'connection_date' => $connectionDate,
                'expire_date' => Carbon::now()->addMonths(1),
                'billing_date' => rand(1, 28),
                'monthly_fee' => $package->price,
                'due_amount' => $dueAmount,
                'status' => $status,
                'connection_status' => $status === 'active' ? (rand(0, 10) > 2 ? 'online' : 'offline') : 'offline',
                'signup_fee' => 500,
                'signup_fee_paid' => true,
                'payment_deadline' => rand(5, 15),
                'auto_disconnect_days' => rand(3, 7),
            ]);
        }

        foreach ($clients as $client) {
            for ($m = 0; $m < rand(3, 12); $m++) {
                $billDate = Carbon::now()->subMonths($m);
                $dueDate = $billDate->copy()->addDays($client->payment_deadline ?? 10);
                $isPaid = $m > 0 || rand(0, 10) > 3;

                $billNo = 'BILL-' . $billDate->format('Ym') . '-' . $client->id;
                $bill = Bill::firstOrCreate(
                    ['bill_no' => $billNo],
                    [
                        'client_id' => $client->id,
                        'bill_date' => $billDate,
                        'due_date' => $dueDate,
                        'package_amount' => $client->monthly_fee,
                        'discount' => rand(0, 5) > 3 ? rand(50, 200) : 0,
                        'total_amount' => $client->monthly_fee,
                        'paid_amount' => $isPaid ? $client->monthly_fee : 0,
                        'due_amount' => $isPaid ? 0 : $client->monthly_fee,
                        'status' => $isPaid ? 'paid' : 'unpaid',
                        'billing_month' => $billDate->month,
                        'billing_year' => $billDate->year,
                    ]
                );

                if ($isPaid && $bill->wasRecentlyCreated) {
                    $paymentDate = $billDate->copy()->addDays(rand(1, 15));
                    static $paymentCounter = 0;
                    $paymentCounter++;
                    $collectedBy = null;
                    if (!empty($employees)) {
                        $emp = $employees[array_rand($employees)];
                        $collectedBy = is_object($emp) ? ($emp->user_id ?? null) : ($emp['user_id'] ?? null);
                    }
                    $paymentNo = 'PAY-' . $paymentDate->format('Ymd') . '-' . str_pad($paymentCounter, 5, '0', STR_PAD_LEFT);
                    Payment::firstOrCreate(
                        ['payment_no' => $paymentNo],
                        [
                            'client_id' => $client->id,
                            'bill_id' => $bill->id,
                            'amount' => $bill->total_amount,
                            'payment_method_id' => $paymentMethods->random()->id,
                            'payment_date' => $paymentDate,
                            'collected_by' => $collectedBy,
                        ]
                    );
                }
            }
        }

        $resellers = [];
        $resellerNames = ['NetZone Reseller', 'SpeedNet Partner', 'FastLink Reseller', 'ConnectBD Partner'];
        foreach ($resellerNames as $i => $name) {
            $resellerUser = User::firstOrCreate(
                ['email' => 'reseller' . ($i + 1) . '@example.com'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'phone' => '019' . str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                    'user_type' => 'reseller',
                    'is_active' => true,
                ]
            );

            $resellers[] = Reseller::firstOrCreate(
                ['user_id' => $resellerUser->id],
                [
                'name' => $name,
                'email' => $resellerUser->email,
                'phone' => $resellerUser->phone,
                'address' => 'Office ' . rand(1, 50) . ', Commercial Area',
                'zone_id' => $zones->random()->id,
                'billing_type' => ['prepaid', 'postpaid'][rand(0, 1)],
                'wallet_balance' => rand(5000, 50000),
                'credit_limit' => rand(10000, 100000),
                'commission_rate' => rand(5, 15),
                'status' => 'active',
            ]);
        }

        $ticketSubjects = [
            'Internet not working', 'Slow connection speed', 'Billing query', 'Need package upgrade',
            'Router configuration issue', 'Frequent disconnection', 'Cannot access certain websites',
            'Need technical support', 'Payment not reflecting', 'Line shifting request'
        ];

        for ($i = 0; $i < 25; $i++) {
            $client = $clients[array_rand($clients)];
            $createdAt = Carbon::now()->subDays(rand(0, 30));
            $status = ['open', 'in_progress', 'resolved', 'closed'][rand(0, 3)];

            $ticket = Ticket::create([
                'client_id' => $client->id,
                'category_id' => $categories->random()->id,
                'subject' => $ticketSubjects[array_rand($ticketSubjects)],
                'description' => 'Customer reported issue with their internet service. Please check and resolve.',
                'priority' => ['low', 'medium', 'high', 'urgent'][rand(0, 3)],
                'status' => $status,
                'assigned_to' => $status !== 'open' ? $this->getEmployeeUserId($employees) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if (rand(0, 1)) {
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $this->getEmployeeUserId($employees) ?? 1,
                    'message' => 'We are looking into your issue. Our technician will contact you shortly.',
                    'is_staff_reply' => true,
                    'created_at' => $createdAt->copy()->addHours(rand(1, 24)),
                ]);
            }
        }

        $expenseDescriptions = [
            'Office rent payment', 'Electricity bill', 'Internet fiber purchase', 'Router maintenance',
            'Staff transport', 'Office supplies', 'Equipment repair', 'Vehicle fuel'
        ];

        for ($i = 0; $i < 30; $i++) {
            $expenseDate = Carbon::now()->subDays(rand(0, 60));
            Expense::create([
                'expense_head_id' => $expenseHeads->random()->id,
                'account_id' => $accounts->first()->id,
                'amount' => rand(500, 15000),
                'expense_date' => $expenseDate,
                'description' => $expenseDescriptions[array_rand($expenseDescriptions)],
                'created_by' => $this->getEmployeeUserId($employees) ?? 1,
                'approval_status' => ['pending', 'approved', 'rejected'][rand(0, 2)],
                'approved_by' => rand(0, 1) ? 1 : null,
                'approved_at' => rand(0, 1) ? $expenseDate->copy()->addHours(rand(1, 48)) : null,
            ]);
        }

        $incomeDescriptions = [
            'Router sale', 'Installation charge', 'Line shifting fee', 'Package upgrade fee',
            'Reconnection fee', 'Late payment fee'
        ];

        for ($i = 0; $i < 20; $i++) {
            $incomeDate = Carbon::now()->subDays(rand(0, 60));
            Income::create([
                'income_head_id' => $incomeHeads->random()->id,
                'account_id' => $accounts->first()->id,
                'amount' => rand(200, 5000),
                'income_date' => $incomeDate,
                'description' => $incomeDescriptions[array_rand($incomeDescriptions)],
                'reference' => 'INC-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'created_by' => 1,
            ]);
        }

        $products = [
            ['name' => 'ONU Device', 'code' => 'ONU-001', 'price' => 1500, 'stock' => 50],
            ['name' => 'Fiber Patch Cord 1m', 'code' => 'FPC-1M', 'price' => 100, 'stock' => 200],
            ['name' => 'Fiber Patch Cord 3m', 'code' => 'FPC-3M', 'price' => 150, 'stock' => 150],
            ['name' => 'Router TP-Link', 'code' => 'RTR-TPL', 'price' => 2500, 'stock' => 30],
            ['name' => 'Splitter 1:4', 'code' => 'SPL-14', 'price' => 500, 'stock' => 40],
            ['name' => 'Splitter 1:8', 'code' => 'SPL-18', 'price' => 800, 'stock' => 25],
            ['name' => 'UTP Cable Cat6 (per meter)', 'code' => 'UTP-C6', 'price' => 25, 'stock' => 1000],
            ['name' => 'Fiber Drop Cable (per meter)', 'code' => 'FDC-01', 'price' => 15, 'stock' => 2000],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['code' => $product['code']],
                [
                    'name' => $product['name'],
                    'selling_price' => $product['price'],
                    'purchase_price' => $product['price'] * 0.7,
                    'stock_quantity' => $product['stock'],
                    'alert_quantity' => 10,
                    'is_active' => true,
                ]
            );
        }

        $mikrotikDevices = [
            ['name' => 'Main Router - Core', 'host' => '192.168.1.1'],
            ['name' => 'OLT Zone A Router', 'host' => '192.168.2.1'],
            ['name' => 'OLT Zone B Router', 'host' => '192.168.3.1'],
            ['name' => 'Building 1 Router', 'host' => '192.168.4.1'],
            ['name' => 'Building 2 Router', 'host' => '192.168.5.1'],
        ];

        foreach ($mikrotikDevices as $device) {
            MikrotikDevice::firstOrCreate(
                ['host' => $device['host']],
                [
                    'name' => $device['name'],
                    'port' => 8728,
                    'username' => 'admin',
                    'password' => 'admin123',
                    'is_active' => true,
                ]
            );
        }

        foreach ($employees as $employee) {
            $employeeId = is_object($employee) ? $employee->id : ($employee['id'] ?? null);
            if (!$employeeId) continue;
            
            for ($d = 0; $d < 30; $d++) {
                $date = Carbon::now()->subDays($d);
                if ($date->isWeekday() && rand(0, 10) > 1) {
                    Attendance::firstOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'attendance_date' => $date->toDateString(),
                        ],
                        [
                            'check_in' => sprintf('%02d:%02d:00', 9, rand(0, 30)),
                            'check_out' => sprintf('%02d:%02d:00', 17, rand(0, 59)),
                            'status' => 'present',
                        ]
                    );
                }
            }
        }
    }
}
