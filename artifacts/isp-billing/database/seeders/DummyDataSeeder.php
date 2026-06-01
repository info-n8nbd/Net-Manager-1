<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\TenantContext;
use App\Models\User;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Models\Income;
use App\Models\IncomeHead;
use App\Models\MikrotikDevice;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    private $bangladeshiNames = [
        'Rahman', 'Karim', 'Islam', 'Hossain', 'Ahmed', 'Khan', 'Akter', 'Begum', 'Chowdhury', 'Mia',
        'Uddin', 'Alam', 'Haque', 'Sultana', 'Khatun', 'Siddique', 'Mahmud', 'Hasan', 'Ali', 'Jahan',
        'Fatima', 'Rashid', 'Nahar', 'Parvin', 'Yasmin', 'Nasreen', 'Bibi', 'Banu', 'Ara', 'Akhter'
    ];
    
    private $firstNames = [
        'Mohammad', 'Abdul', 'Md.', 'Shah', 'Sk.', 'Kamrul', 'Jahangir', 'Rafiqul', 'Shafiqul', 'Aminul',
        'Mizanur', 'Nazrul', 'Khairul', 'Habibur', 'Golam', 'Faruk', 'Ashraf', 'Masud', 'Jamal', 'Kamal',
        'Fatema', 'Mst.', 'Sadia', 'Nazia', 'Taslima', 'Roksana', 'Shahnaz', 'Nasrin', 'Halima', 'Rashida'
    ];

    public function run(): void
    {
        TenantContext::setSuperAdminMode(true);
        $zones = Zone::all();
        $packages = Package::all();
        $paymentMethods = PaymentMethod::all();
        $admin = User::where('email', 'admin@isp.com')->first();
        $departments = Department::all();
        $designations = Designation::all();
        $ticketCategories = TicketCategory::all();
        $expenseHeads = ExpenseHead::all();
        $incomeHeads = IncomeHead::all();
        $account = Account::first();

        $this->createMikrotikDevices($zones);
        $employees = $this->createEmployees($departments, $designations);
        $clients = $this->createClients($zones, $packages, 75);
        $this->createBillsAndPayments($clients, $paymentMethods, $admin);
        $this->createTickets($clients, $ticketCategories, $employees, $admin);
        $this->createExpenses($expenseHeads, $admin, $account);
        $this->createIncome($incomeHeads, $admin, $account);
    }

    private function createMikrotikDevices($zones): void
    {
        $zoneIds = $zones->pluck('id')->toArray();
        $devices = [
            ['name' => 'Main Router - Dhaka', 'host' => '192.168.1.1', 'port' => 8728, 'username' => 'admin', 'password' => 'admin123', 'description' => 'Main office router', 'zone_id' => $zoneIds[0] ?? null, 'is_active' => true],
            ['name' => 'POP Router - Gulshan', 'host' => '192.168.2.1', 'port' => 8728, 'username' => 'admin', 'password' => 'admin123', 'description' => 'Gulshan POP router', 'zone_id' => $zoneIds[1] ?? null, 'is_active' => true],
            ['name' => 'POP Router - Uttara', 'host' => '192.168.3.1', 'port' => 8728, 'username' => 'admin', 'password' => 'admin123', 'description' => 'Uttara POP router', 'zone_id' => $zoneIds[2] ?? null, 'is_active' => true],
            ['name' => 'OLT Switch - Zone A', 'host' => '192.168.10.1', 'port' => 8728, 'username' => 'admin', 'password' => 'admin123', 'description' => 'Zone A OLT', 'zone_id' => $zoneIds[3] ?? null, 'is_active' => true],
        ];
        foreach ($devices as $device) {
            MikrotikDevice::create($device);
        }
    }

    private function createEmployees($departments, $designations): array
    {
        $employees = [];
        $employeeData = [
            ['name' => 'Rafiqul Islam', 'email' => 'rafiq@isp.com', 'phone' => '01712345678', 'dept' => 'Technical', 'desig' => 'Technician', 'salary' => 18000],
            ['name' => 'Kamal Hossain', 'email' => 'kamal@isp.com', 'phone' => '01712345679', 'dept' => 'Technical', 'desig' => 'Technician', 'salary' => 18000],
            ['name' => 'Jahangir Alam', 'email' => 'jahangir@isp.com', 'phone' => '01712345680', 'dept' => 'Technical', 'desig' => 'Manager', 'salary' => 35000],
            ['name' => 'Shahnaz Begum', 'email' => 'shahnaz@isp.com', 'phone' => '01712345681', 'dept' => 'Sales', 'desig' => 'Sales Executive', 'salary' => 22000],
            ['name' => 'Nasrin Akter', 'email' => 'nasrin@isp.com', 'phone' => '01712345682', 'dept' => 'Sales', 'desig' => 'Sales Executive', 'salary' => 20000],
            ['name' => 'Aminul Haque', 'email' => 'aminul@isp.com', 'phone' => '01712345683', 'dept' => 'Accounts', 'desig' => 'Accountant', 'salary' => 25000],
            ['name' => 'Mizanur Rahman', 'email' => 'mizan@isp.com', 'phone' => '01712345684', 'dept' => 'Technical', 'desig' => 'Technician', 'salary' => 17000],
            ['name' => 'Faruk Ahmed', 'email' => 'faruk@isp.com', 'phone' => '01712345685', 'dept' => 'Administration', 'desig' => 'Manager', 'salary' => 40000],
        ];

        foreach ($employeeData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'phone' => $data['phone'],
                'user_type' => 'employee',
                'is_active' => true,
            ]);

            $dept = $departments->where('name', $data['dept'])->first();
            $desig = $designations->where('name', $data['desig'])->first();

            $employees[] = Employee::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => 'Dhaka, Bangladesh',
                'nid' => rand(1000000000, 9999999999),
                'date_of_birth' => Carbon::now()->subYears(rand(25, 45))->subDays(rand(1, 365)),
                'gender' => rand(0, 1) ? 'male' : 'female',
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
                'joining_date' => Carbon::now()->subYears(rand(1, 5))->subDays(rand(1, 365)),
                'basic_salary' => $data['salary'],
                'status' => 'active',
            ]);
        }
        return $employees;
    }

    private function createClients($zones, $packages, $count): array
    {
        $clients = [];
        $addresses = [
            'House 12, Road 5, Dhanmondi', 'Flat 3A, Green Tower, Gulshan', 'Building 7, Sector 4, Uttara',
            'Plot 23, Block C, Banani', 'House 45, Road 8, Mohammadpur', 'Flat 5B, City Center, Mirpur',
            'House 78, Road 12, Bashundhara', 'Shop 15, Market Area, Motijheel', 'House 34, Lane 6, Khilgaon',
            'Flat 2C, Park View, Tejgaon', 'House 56, Road 3, Rampura', 'Building 9, Block A, Malibagh',
        ];

        for ($i = 0; $i < $count; $i++) {
            $zone = $zones->random();
            $area = Area::where('zone_id', $zone->id)->inRandomOrder()->first();
            $package = $packages->random();
            $firstName = $this->firstNames[array_rand($this->firstNames)];
            $lastName = $this->bangladeshiNames[array_rand($this->bangladeshiNames)];
            $name = $firstName . ' ' . $lastName;
            $connectionDate = Carbon::now()->subDays(rand(30, 365));
            $status = rand(1, 100) <= 85 ? 'active' : (rand(0, 1) ? 'inactive' : 'pending');
            $connectionStatus = $status === 'active' ? 'online' : 'offline';

            $user = User::create([
                'name' => $name,
                'email' => 'client' . ($i + 1) . '@isp.com',
                'password' => Hash::make('password'),
                'phone' => '017' . rand(10000000, 99999999),
                'user_type' => 'client',
                'is_active' => $status === 'active',
            ]);

            $clients[] = Client::create([
                'user_id' => $user->id,
                'name' => $name,
                'email' => $user->email,
                'phone' => $user->phone,
                'alt_phone' => rand(0, 1) ? '018' . rand(10000000, 99999999) : null,
                'address' => $addresses[array_rand($addresses)] . ', ' . $zone->name,
                'nid' => rand(1000000000, 9999999999),
                'zone_id' => $zone->id,
                'area_id' => $area->id,
                'package_id' => $package->id,
                'username' => 'pppoe_user_' . ($i + 1),
                'pppoe_password' => 'pass' . rand(1000, 9999),
                'connection_date' => $connectionDate,
                'billing_date' => $connectionDate->day,
                'monthly_fee' => $package->price,
                'permanent_discount' => rand(0, 3) === 0 ? rand(50, 200) : 0,
                'status' => $status,
                'connection_status' => $connectionStatus,
                'connection_type' => 'pppoe',
                'signup_fee' => rand(500, 2000),
                'signup_fee_paid' => true,
                'payment_deadline' => 10,
                'auto_disconnect_days' => 15,
                'google_map_lat' => 23.8103 + (rand(-1000, 1000) / 10000),
                'google_map_lng' => 90.4125 + (rand(-1000, 1000) / 10000),
            ]);
        }
        return $clients;
    }

    private function createBillsAndPayments($clients, $paymentMethods, $admin): void
    {
        $today = Carbon::now();
        
        foreach ($clients as $client) {
            $connectionDate = Carbon::parse($client->connection_date);
            $monthsActive = $connectionDate->diffInMonths($today);
            $monthsToGenerate = min($monthsActive, 6);

            for ($m = $monthsToGenerate; $m >= 0; $m--) {
                $billDate = $today->copy()->subMonths($m)->startOfMonth();
                $dueDate = $billDate->copy()->addDays(10);
                $packageAmount = $client->monthly_fee;
                $discount = $client->permanent_discount;
                $totalAmount = $packageAmount - $discount;
                
                $isPaid = $m > 0 ? rand(1, 100) <= 85 : rand(1, 100) <= 60;
                $paidAmount = $isPaid ? $totalAmount : (rand(0, 1) && $m === 0 ? rand(0, intval($totalAmount)) : 0);
                $dueAmount = $totalAmount - $paidAmount;
                $status = $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

                $bill = Bill::create([
                    'client_id' => $client->id,
                    'billing_month' => $billDate->month,
                    'billing_year' => $billDate->year,
                    'bill_date' => $billDate,
                    'due_date' => $dueDate,
                    'package_amount' => $packageAmount,
                    'previous_due' => 0,
                    'adjustment' => 0,
                    'discount' => $discount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                    'status' => $status,
                    'generated_by' => $admin->id,
                ]);

                if ($paidAmount > 0) {
                    $paymentDate = $billDate->copy()->addDays(rand(1, 15));
                    Payment::create([
                        'client_id' => $client->id,
                        'bill_id' => $bill->id,
                        'amount' => $paidAmount,
                        'payment_method_id' => $paymentMethods->random()->id,
                        'payment_date' => $paymentDate,
                        'transaction_id' => $paymentMethods->random()->code === 'BKASH' ? 'TRX' . rand(100000, 999999) : null,
                        'collected_by' => $admin->id,
                    ]);
                }
            }

            $client->update([
                'due_amount' => Bill::where('client_id', $client->id)->sum('due_amount'),
            ]);
        }
    }

    private function createTickets($clients, $categories, $employees, $admin): void
    {
        $subjects = [
            'Internet Issue' => ['Slow internet speed', 'No internet connection', 'Frequent disconnection', 'Cannot connect to PPPoE'],
            'Billing Issue' => ['Bill amount incorrect', 'Payment not reflected', 'Discount not applied', 'Need bill copy'],
            'New Connection' => ['Apply for new connection', 'Connection installation pending', 'Router setup required'],
            'Technical Support' => ['Router configuration needed', 'WiFi not working', 'IP conflict issue', 'Need password reset'],
        ];

        $technicians = collect($employees)->filter(fn($e) => $e->department->name === 'Technical');

        for ($i = 0; $i < 40; $i++) {
            $client = $clients[array_rand($clients)];
            $category = $categories->random();
            $categorySubjects = $subjects[$category->name] ?? ['General inquiry'];
            $subject = $categorySubjects[array_rand($categorySubjects)];
            $priority = ['low', 'medium', 'high', 'urgent'][rand(0, 3)];
            $status = ['open', 'in_progress', 'resolved', 'closed'][rand(0, 3)];
            $createdDate = Carbon::now()->subDays(rand(1, 60));

            $ticket = Ticket::create([
                'client_id' => $client->id,
                'category_id' => $category->id,
                'subject' => $subject,
                'description' => 'Customer reported: ' . $subject . '. Please look into this issue.',
                'priority' => $priority,
                'status' => $status,
                'assigned_to' => $admin->id,
                'technician_id' => $status !== 'open' && $technicians->count() > 0 ? $technicians->random()->user_id : null,
                'sla_deadline' => $createdDate->copy()->addHours($priority === 'urgent' ? 4 : ($priority === 'high' ? 8 : 24)),
                'sla_breached' => rand(0, 10) < 2,
                'satisfaction_rating' => $status === 'closed' ? rand(3, 5) : null,
                'resolved_at' => in_array($status, ['resolved', 'closed']) ? $createdDate->copy()->addHours(rand(2, 48)) : null,
                'closed_at' => $status === 'closed' ? $createdDate->copy()->addHours(rand(24, 72)) : null,
                'created_at' => $createdDate,
                'updated_at' => $createdDate,
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $client->user_id,
                'message' => 'I am facing this issue: ' . $subject . '. Please help.',
                'created_at' => $createdDate,
            ]);

            if ($status !== 'open') {
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $admin->id,
                    'message' => 'Thank you for reporting. Our technician will look into this issue.',
                    'created_at' => $createdDate->copy()->addMinutes(rand(10, 120)),
                ]);
            }
        }
    }

    private function createExpenses($expenseHeads, $admin, $account): void
    {
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays(rand(1, 90));
            $head = $expenseHeads->random();
            $amount = rand(500, 15000);

            Expense::create([
                'expense_head_id' => $head->id,
                'account_id' => $account->id,
                'amount' => $amount,
                'description' => $head->name . ' expense',
                'expense_date' => $date,
                'created_by' => $admin->id,
                'created_at' => $date,
            ]);
        }
    }

    private function createIncome($incomeHeads, $admin, $account): void
    {
        for ($i = 0; $i < 20; $i++) {
            $date = Carbon::now()->subDays(rand(1, 90));
            $head = $incomeHeads->random();
            $amount = rand(1000, 25000);

            Income::create([
                'income_head_id' => $head->id,
                'account_id' => $account->id,
                'amount' => $amount,
                'description' => $head->name . ' - ' . date('F Y', strtotime($date)),
                'income_date' => $date,
                'created_by' => $admin->id,
                'created_at' => $date,
            ]);
        }
    }
}
