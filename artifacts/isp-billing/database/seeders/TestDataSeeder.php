<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        
        echo "Starting test data seeding...\n\n";
        
        // 1. Add OLT Devices (already added)
        $this->seedOltDevices($now);
        
        // 2. Add more Clients (already added)
        $this->seedMoreClients($now);
        
        // 3. Add New Connection Applications (already added)
        $this->seedConnectionApplications($now);
        
        // 4. Add Expenses (already added)
        $this->seedExpenses($now);
        
        // 5. Add Incomes (already added)
        $this->seedIncomes($now);
        
        // 6. Add SMS Logs (already added)
        $this->seedSmsLogs($now);
        
        // 7. Add Attendance Records
        $this->seedAttendance($now);
        
        // 8. Add Staff Dues
        $this->seedStaffDues($now);
        
        // 9. Add More Tickets
        $this->seedMoreTickets($now);
        
        // 10. Add Leaves
        $this->seedLeaves($now);
        
        // 11. Add Fund Transfers
        $this->seedFundTransfers($now);
        
        // 12. Add Discounts/Promos
        $this->seedDiscounts($now);
        
        // 13. Add More Payments
        $this->seedMorePayments($now);
        
        // 14. Add More Bills
        $this->seedMoreBills($now);
        
        // 15. Add Activity Logs
        $this->seedActivityLogs($now);
        
        echo "\nTest data seeded successfully!\n";
    }
    
    private function seedOltDevices($now)
    {
        if (DB::table('olt_devices')->count() >= 3) {
            echo "- OLT Devices: Already exists, skipping\n";
            return;
        }
        
        $oltDevices = [
            ['name' => 'OLT-Main-DC', 'ip_address' => '192.168.100.1', 'vendor' => 'Huawei', 'model' => 'MA5608T', 'total_ports' => 8, 'port' => 1, 'location' => 'Main Data Center', 'is_active' => true, 'username' => 'admin', 'password' => 'admin123', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'OLT-Zone1', 'ip_address' => '192.168.100.2', 'vendor' => 'ZTE', 'model' => 'C320', 'total_ports' => 16, 'port' => 1, 'location' => 'Zone 1 POP', 'is_active' => true, 'username' => 'admin', 'password' => 'admin123', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'OLT-Zone2', 'ip_address' => '192.168.100.3', 'vendor' => 'BDCOM', 'model' => 'GP3600-08', 'total_ports' => 8, 'port' => 1, 'location' => 'Zone 2 POP', 'is_active' => true, 'username' => 'admin', 'password' => 'admin123', 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::table('olt_devices')->insertOrIgnore($oltDevices);
        echo "- OLT Devices: 3 added\n";
    }
    
    private function seedMoreClients($now)
    {
        $zones = DB::table('zones')->pluck('id')->toArray();
        $areas = DB::table('areas')->pluck('id')->toArray();
        $packages = DB::table('packages')->pluck('id')->toArray();
        
        if (empty($zones) || empty($packages)) return;
        
        // Check if test clients already exist
        if (DB::table('clients')->where('client_id', 'like', 'CLI-TEST-%')->count() >= 30) {
            echo "- Clients: Test clients already exist, skipping\n";
            return;
        }
        
        $statuses = ['active', 'active', 'active', 'inactive', 'inactive'];
        $clientsToAdd = [];
        
        for ($i = 1; $i <= 30; $i++) {
            $clientsToAdd[] = [
                'client_id' => 'CLI-TEST-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => 'Test Client ' . $i,
                'phone' => '0171' . rand(1000000, 9999999),
                'email' => 'testclient' . $i . '@example.com',
                'address' => 'House ' . rand(1, 100) . ', Road ' . rand(1, 20) . ', Block ' . chr(64 + rand(1, 10)),
                'zone_id' => $zones[array_rand($zones)],
                'area_id' => !empty($areas) ? $areas[array_rand($areas)] : null,
                'package_id' => $packages[array_rand($packages)],
                'connection_date' => $now->copy()->subDays(rand(30, 365))->format('Y-m-d'),
                'billing_date' => rand(1, 28),
                'status' => $statuses[array_rand($statuses)],
                'connection_type' => ['pppoe', 'queue', 'invoice'][rand(0, 2)],
                'username' => 'testuser' . $i,
                'pppoe_password' => 'pass' . rand(1000, 9999),
                'balance' => rand(-2000, 5000),
                'monthly_fee' => rand(500, 2000),
                'nid' => strval(rand(1000000000, 9999999999)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('clients')->insertOrIgnore($clientsToAdd);
        echo "- Clients: 30 test clients added\n";
    }
    
    private function seedConnectionApplications($now)
    {
        $zones = DB::table('zones')->pluck('id')->toArray();
        $packages = DB::table('packages')->pluck('id')->toArray();
        $technicians = DB::table('users')->where('user_type', 'technician')->pluck('id')->toArray();
        
        if (empty($zones) || empty($packages)) return;
        
        // Check if already seeded
        if (DB::table('new_connection_applications')->count() >= 15) {
            echo "- Connection Applications: Already exist, skipping\n";
            return;
        }
        
        $statuses = ['draft', 'submitted', 'under_review', 'need_correction', 'approved', 'rejected'];
        $applications = [];
        
        for ($i = 1; $i <= 15; $i++) {
            $applications[] = [
                'application_number' => 'APP-' . date('Ym') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'full_name_english' => 'Applicant ' . $i,
                'full_name_bangla' => 'আবেদনকারী ' . $i,
                'mobile_number' => '0181' . rand(1000000, 9999999),
                'email' => 'applicant' . $i . '@example.com',
                'house_road_floor' => 'House ' . rand(1, 50) . ', Road ' . rand(1, 15),
                'nid_number' => strval(rand(1000000000, 9999999999)),
                'zone_id' => $zones[array_rand($zones)],
                'package_id' => $packages[array_rand($packages)],
                'status' => $statuses[array_rand($statuses)],
                'technician_id' => !empty($technicians) ? $technicians[array_rand($technicians)] : null,
                'technician_note' => $i % 3 == 0 ? 'Priority customer' : null,
                'connection_type' => rand(0, 1) ? 'home' : 'corporate',
                'gender' => rand(0, 1) ? 'male' : 'female',
                'created_at' => $now->copy()->subDays(rand(1, 30)),
                'updated_at' => $now,
            ];
        }
        
        DB::table('new_connection_applications')->insertOrIgnore($applications);
        echo "- Connection Applications: 15 added\n";
    }
    
    private function seedExpenses($now)
    {
        $expenseHeads = DB::table('expense_heads')->pluck('id')->toArray();
        $accounts = DB::table('accounts')->pluck('id')->toArray();
        
        if (empty($expenseHeads) || empty($accounts)) return;
        
        if (DB::table('expenses')->count() >= 25) {
            echo "- Expenses: Already exist, skipping\n";
            return;
        }
        
        $expenses = [];
        for ($i = 1; $i <= 25; $i++) {
            $expenses[] = [
                'expense_head_id' => $expenseHeads[array_rand($expenseHeads)],
                'account_id' => $accounts[array_rand($accounts)],
                'amount' => rand(500, 50000),
                'expense_date' => $now->copy()->subDays(rand(1, 60))->format('Y-m-d'),
                'description' => 'Expense entry #' . $i,
                'reference' => 'EXP-' . date('Ym') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'approval_status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('expenses')->insertOrIgnore($expenses);
        echo "- Expenses: 25 added\n";
    }
    
    private function seedIncomes($now)
    {
        $incomeHeads = DB::table('income_heads')->pluck('id')->toArray();
        $accounts = DB::table('accounts')->pluck('id')->toArray();
        
        if (empty($incomeHeads) || empty($accounts)) return;
        
        if (DB::table('incomes')->count() >= 20) {
            echo "- Incomes: Already exist, skipping\n";
            return;
        }
        
        $incomes = [];
        for ($i = 1; $i <= 20; $i++) {
            $incomes[] = [
                'income_head_id' => $incomeHeads[array_rand($incomeHeads)],
                'account_id' => $accounts[array_rand($accounts)],
                'amount' => rand(1000, 100000),
                'income_date' => $now->copy()->subDays(rand(1, 60))->format('Y-m-d'),
                'description' => 'Income entry #' . $i,
                'reference' => 'INC-' . date('Ym') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('incomes')->insertOrIgnore($incomes);
        echo "- Incomes: 20 added\n";
    }
    
    private function seedSmsLogs($now)
    {
        $clients = DB::table('clients')->pluck('id')->toArray();
        
        if (DB::table('sms_logs')->count() >= 50) {
            echo "- SMS Logs: Already exist, skipping\n";
            return;
        }
        
        $statuses = ['sent', 'sent', 'sent', 'failed', 'pending'];
        
        $smsLogs = [];
        for ($i = 1; $i <= 50; $i++) {
            $smsLogs[] = [
                'client_id' => !empty($clients) ? $clients[array_rand($clients)] : null,
                'phone' => '0171' . rand(1000000, 9999999),
                'message' => 'Test SMS message #' . $i . ' - Lorem ipsum dolor sit amet',
                'status' => $statuses[array_rand($statuses)],
                'created_at' => $now->copy()->subHours(rand(1, 720)),
                'updated_at' => $now,
            ];
        }
        
        DB::table('sms_logs')->insertOrIgnore($smsLogs);
        echo "- SMS Logs: 50 added\n";
    }
    
    private function seedAttendance($now)
    {
        $employees = DB::table('employees')->pluck('id')->toArray();
        
        if (empty($employees)) return;
        
        if (DB::table('attendances')->count() >= 100) {
            echo "- Attendance: Already exist, skipping\n";
            return;
        }
        
        $records = [];
        foreach ($employees as $empId) {
            for ($day = 1; $day <= 15; $day++) {
                $date = $now->copy()->subDays($day);
                if ($date->isWeekend()) continue;
                
                $checkIn = $date->copy()->setTime(rand(8, 10), rand(0, 59));
                $checkOut = $checkIn->copy()->addHours(rand(8, 10));
                
                $records[] = [
                    'employee_id' => $empId,
                    'attendance_date' => $date->format('Y-m-d'),
                    'check_in' => $checkIn->format('H:i:s'),
                    'check_out' => $checkOut->format('H:i:s'),
                    'status' => rand(0, 10) > 1 ? 'present' : 'late',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        
        DB::table('attendances')->insertOrIgnore($records);
        echo "- Attendance: " . count($records) . " records added\n";
    }
    
    private function seedStaffDues($now)
    {
        $users = DB::table('users')->whereIn('user_type', ['technician', 'employee'])->pluck('id')->toArray();
        
        if (empty($users)) return;
        
        if (DB::table('staff_dues')->count() >= count($users)) {
            echo "- Staff Dues: Already exist, skipping\n";
            return;
        }
        
        $dues = [];
        foreach ($users as $userId) {
            $collected = rand(10000, 100000);
            $submitted = rand(5000, $collected);
            $dues[] = [
                'user_id' => $userId,
                'total_collected' => $collected,
                'total_submitted' => $submitted,
                'current_due' => $collected - $submitted,
                'last_collection_date' => $now->copy()->subDays(rand(1, 30))->format('Y-m-d'),
                'last_submission_date' => $now->copy()->subDays(rand(1, 15))->format('Y-m-d'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('staff_dues')->insertOrIgnore($dues);
        echo "- Staff Dues: " . count($dues) . " added\n";
    }
    
    private function seedMoreTickets($now)
    {
        $clients = DB::table('clients')->pluck('id')->toArray();
        $categories = DB::table('ticket_categories')->pluck('id')->toArray();
        $technicians = DB::table('users')->where('user_type', 'technician')->pluck('id')->toArray();
        
        if (empty($clients) || empty($categories)) return;
        
        if (DB::table('tickets')->count() >= 20) {
            echo "- Support Tickets: Already exist, skipping\n";
            return;
        }
        
        $subjects = ['Internet Not Working', 'Slow Speed', 'Frequent Disconnection', 'Router Issue', 'Bill Query', 'Package Upgrade', 'ONU Light Red', 'No Signal'];
        $priorities = ['low', 'medium', 'medium', 'high', 'urgent'];
        $statuses = ['open', 'open', 'in_progress', 'resolved', 'closed'];
        
        $tickets = [];
        for ($i = 1; $i <= 20; $i++) {
            $tickets[] = [
                'ticket_no' => 'TKT-' . date('Ym') . '-' . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                'client_id' => $clients[array_rand($clients)],
                'category_id' => $categories[array_rand($categories)],
                'subject' => $subjects[array_rand($subjects)],
                'description' => 'Customer reported issue: ' . $subjects[array_rand($subjects)] . '. Needs immediate attention.',
                'priority' => $priorities[array_rand($priorities)],
                'status' => $statuses[array_rand($statuses)],
                'assigned_to' => !empty($technicians) ? $technicians[array_rand($technicians)] : null,
                'created_at' => $now->copy()->subDays(rand(1, 30)),
                'updated_at' => $now,
            ];
        }
        
        DB::table('tickets')->insertOrIgnore($tickets);
        echo "- Support Tickets: 20 added\n";
    }
    
    private function seedLeaves($now)
    {
        $employees = DB::table('employees')->pluck('id')->toArray();
        
        if (empty($employees)) return;
        
        if (DB::table('leaves')->count() >= 5) {
            echo "- Leaves: Already exist, skipping\n";
            return;
        }
        
        $leaveTypes = ['annual', 'sick', 'casual', 'unpaid', 'other'];
        $statuses = ['pending', 'approved', 'approved', 'rejected'];
        
        $leaves = [];
        foreach ($employees as $empId) {
            if (rand(0, 2) > 0) continue;
            
            $startDate = $now->copy()->subDays(rand(1, 60));
            $days = rand(1, 5);
            $leaves[] = [
                'employee_id' => $empId,
                'leave_type' => $leaveTypes[array_rand($leaveTypes)],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $startDate->copy()->addDays($days)->format('Y-m-d'),
                'total_days' => $days,
                'reason' => 'Personal reasons',
                'status' => $statuses[array_rand($statuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('leaves')->insertOrIgnore($leaves);
        echo "- Leaves: " . count($leaves) . " added\n";
    }
    
    private function seedFundTransfers($now)
    {
        $accounts = DB::table('accounts')->pluck('id')->toArray();
        
        if (count($accounts) < 2) return;
        
        if (DB::table('fund_transfers')->count() >= 10) {
            echo "- Fund Transfers: Already exist, skipping\n";
            return;
        }
        
        $transfers = [];
        for ($i = 1; $i <= 10; $i++) {
            $fromAccount = $accounts[array_rand($accounts)];
            $toAccount = $accounts[array_rand($accounts)];
            while ($toAccount == $fromAccount && count($accounts) > 1) {
                $toAccount = $accounts[array_rand($accounts)];
            }
            
            $transfers[] = [
                'from_account_id' => $fromAccount,
                'to_account_id' => $toAccount,
                'amount' => rand(5000, 100000),
                'transfer_date' => $now->copy()->subDays(rand(1, 60))->format('Y-m-d'),
                'description' => 'Fund transfer #' . $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('fund_transfers')->insertOrIgnore($transfers);
        echo "- Fund Transfers: 10 added\n";
    }
    
    private function seedDiscounts($now)
    {
        if (DB::table('discounts')->count() >= 4) {
            echo "- Discounts: Already exist, skipping\n";
            return;
        }
        
        $discounts = [
            ['name' => 'Welcome Discount', 'type' => 'percentage', 'value' => 10, 'valid_from' => $now->copy()->subMonths(2)->format('Y-m-d'), 'valid_to' => $now->copy()->addMonths(6)->format('Y-m-d'), 'is_active' => true],
            ['name' => 'Flat 500 Off', 'type' => 'fixed', 'value' => 500, 'valid_from' => $now->copy()->subMonths(1)->format('Y-m-d'), 'valid_to' => $now->copy()->addMonths(3)->format('Y-m-d'), 'is_active' => true],
            ['name' => 'Loyalty Bonus', 'type' => 'percentage', 'value' => 15, 'valid_from' => $now->copy()->subMonths(3)->format('Y-m-d'), 'valid_to' => $now->copy()->addMonths(9)->format('Y-m-d'), 'is_active' => true],
            ['name' => 'Summer Offer', 'type' => 'percentage', 'value' => 20, 'valid_from' => $now->copy()->subMonths(6)->format('Y-m-d'), 'valid_to' => $now->copy()->subMonths(3)->format('Y-m-d'), 'is_active' => false],
        ];
        
        foreach ($discounts as &$discount) {
            $discount['created_at'] = $now;
            $discount['updated_at'] = $now;
        }
        
        DB::table('discounts')->insertOrIgnore($discounts);
        echo "- Discounts/Promo Codes: 4 added\n";
    }
    
    private function seedMorePayments($now)
    {
        $clients = DB::table('clients')->pluck('id')->toArray();
        $paymentMethods = DB::table('payment_methods')->pluck('id')->toArray();
        $collectors = DB::table('users')->whereIn('user_type', ['technician', 'admin'])->pluck('id')->toArray();
        
        if (empty($clients) || empty($paymentMethods)) return;
        
        if (DB::table('payments')->count() >= 250) {
            echo "- Additional Payments: Already exist, skipping\n";
            return;
        }
        
        $payments = [];
        for ($i = 1; $i <= 50; $i++) {
            $payments[] = [
                'payment_no' => 'PAY-' . date('Ym') . '-' . str_pad($i + 500, 5, '0', STR_PAD_LEFT),
                'client_id' => $clients[array_rand($clients)],
                'amount' => rand(500, 3000),
                'payment_method_id' => $paymentMethods[array_rand($paymentMethods)],
                'payment_date' => $now->copy()->subDays(rand(1, 90))->format('Y-m-d'),
                'transaction_id' => 'TXN' . rand(100000, 999999),
                'collected_by' => !empty($collectors) ? $collectors[array_rand($collectors)] : null,
                'remarks' => rand(0, 2) == 0 ? 'Monthly payment' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('payments')->insertOrIgnore($payments);
        echo "- Additional Payments: 50 added\n";
    }
    
    private function seedMoreBills($now)
    {
        $clients = DB::table('clients')->pluck('id')->toArray();
        
        if (empty($clients)) return;
        
        if (DB::table('bills')->count() >= 250) {
            echo "- Additional Bills: Already exist, skipping\n";
            return;
        }
        
        $bills = [];
        for ($i = 1; $i <= 40; $i++) {
            $billDate = $now->copy()->subDays(rand(1, 90));
            $dueDate = $billDate->copy()->addDays(15);
            $packageAmount = rand(500, 3000);
            $discount = rand(0, 1) ? rand(50, 200) : 0;
            $totalAmount = $packageAmount - $discount;
            $paidAmount = rand(0, $totalAmount);
            
            $bills[] = [
                'client_id' => $clients[array_rand($clients)],
                'bill_no' => 'BILL-' . date('Ym') . '-' . str_pad($i + 500, 5, '0', STR_PAD_LEFT),
                'billing_month' => $billDate->format('m'),
                'billing_year' => $billDate->format('Y'),
                'package_amount' => $packageAmount,
                'previous_due' => rand(0, 500),
                'adjustment' => 0,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $totalAmount - $paidAmount,
                'bill_date' => $billDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('bills')->insertOrIgnore($bills);
        echo "- Additional Bills: 40 added\n";
    }
    
    private function seedActivityLogs($now)
    {
        $users = DB::table('users')->pluck('id')->toArray();
        
        if (empty($users)) return;
        
        if (DB::table('activity_logs')->count() >= 50) {
            echo "- Activity Logs: Already exist, skipping\n";
            return;
        }
        
        $actions = ['login', 'logout', 'create_client', 'update_client', 'create_payment', 'create_bill', 'update_settings', 'view_report'];
        $descriptions = [
            'User logged into the system',
            'User logged out',
            'Created new client record',
            'Updated client information',
            'Recorded payment from client',
            'Generated monthly bill',
            'Updated system settings',
            'Viewed collection report',
        ];
        
        $modules = ['clients', 'payments', 'bills', 'auth', 'settings', 'reports', 'users'];
        
        $logs = [];
        for ($i = 1; $i <= 50; $i++) {
            $actionIndex = array_rand($actions);
            $logs[] = [
                'user_id' => $users[array_rand($users)],
                'action' => $actions[$actionIndex],
                'module' => $modules[array_rand($modules)],
                'description' => $descriptions[$actionIndex],
                'ip_address' => '192.168.1.' . rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
                'created_at' => $now->copy()->subHours(rand(1, 720)),
                'updated_at' => $now,
            ];
        }
        
        DB::table('activity_logs')->insertOrIgnore($logs);
        echo "- Activity Logs: 50 added\n";
    }
}
