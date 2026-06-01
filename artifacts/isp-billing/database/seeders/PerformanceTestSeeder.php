<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\TenantContext;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceTestSeeder extends Seeder
{
    private $firstNames = [
        'Mohammad', 'Abdul', 'Md.', 'Sk.', 'Rafiq', 'Karim', 'Rahim', 'Jamal', 'Hasan', 'Hussain',
        'Fatima', 'Ayesha', 'Khadija', 'Amina', 'Salma', 'Nasrin', 'Ruma', 'Sadia', 'Nusrat', 'Taslima',
        'Ahmed', 'Ali', 'Khan', 'Sheikh', 'Mia', 'Uddin', 'Rahman', 'Islam', 'Hossain', 'Akter',
        'Rubel', 'Sumon', 'Raju', 'Mamun', 'Jahid', 'Shakil', 'Arif', 'Sohel', 'Ripon', 'Polash',
        'Shirin', 'Jannatul', 'Mst.', 'Rahima', 'Kulsum', 'Monira', 'Shamima', 'Hosna', 'Jesmin', 'Parveen'
    ];

    private $lastNames = [
        'Ahmed', 'Hossain', 'Rahman', 'Khan', 'Ali', 'Islam', 'Uddin', 'Mia', 'Sheikh', 'Chowdhury',
        'Begum', 'Khatun', 'Akter', 'Sultana', 'Jahan', 'Banu', 'Parvin', 'Nahar', 'Yeasmin', 'Rani',
        'Sarker', 'Sikder', 'Mondol', 'Biswas', 'Das', 'Roy', 'Ghosh', 'Barman', 'Talukder', 'Pramanik',
        'Siddique', 'Kabir', 'Haque', 'Bhuiyan', 'Mahmud', 'Rashid', 'Kamal', 'Amin', 'Reza', 'Nawaz'
    ];

    private $areas = [
        'Mirpur', 'Uttara', 'Gulshan', 'Banani', 'Dhanmondi', 'Mohammadpur', 'Badda', 'Rampura',
        'Khilgaon', 'Motijheel', 'Jatrabari', 'Demra', 'Tejgaon', 'Shyamoli', 'Agargaon', 'Farmgate',
        'Malibagh', 'Mugda', 'Basabo', 'Shantinagar', 'Paltan', 'Wari', 'Sutrapur', 'Lalbagh',
        'Kamrangirchar', 'Hazaribagh', 'Keraniganj', 'Narayanganj', 'Tongi', 'Gazipur'
    ];

    private $roads = ['Road', 'Lane', 'Gali', 'Street', 'Block', 'Sector', 'Avenue'];

    public function run(): void
    {
        TenantContext::setSuperAdminMode(true);
        $this->command->info('Starting Performance Test Data Seeder - Adding 1000 clients...');
        
        $zones = Zone::all();
        $areas = Area::all();
        $packages = Package::all();
        $paymentMethods = PaymentMethod::all();
        $admin = User::where('user_type', 'admin')->first();
        $technicians = User::where('user_type', 'technician')->get();

        if ($zones->isEmpty() || $packages->isEmpty()) {
            $this->command->error('Please run DatabaseSeeder first to create zones and packages.');
            return;
        }

        $startTime = microtime(true);
        $clientCount = 1000;
        $batchSize = 100;

        DB::beginTransaction();
        
        try {
            for ($batch = 0; $batch < $clientCount / $batchSize; $batch++) {
                $clients = [];
                
                for ($i = 0; $i < $batchSize; $i++) {
                    $clientNum = ($batch * $batchSize) + $i + 1;
                    $zone = $zones->random();
                    $area = $areas->where('zone_id', $zone->id)->first() ?? $areas->random();
                    $package = $packages->random();
                    
                    $firstName = $this->firstNames[array_rand($this->firstNames)];
                    $lastName = $this->lastNames[array_rand($this->lastNames)];
                    $name = $firstName . ' ' . $lastName;
                    
                    $areaName = $this->areas[array_rand($this->areas)];
                    $roadNum = rand(1, 20);
                    $houseNum = rand(1, 100);
                    $roadType = $this->roads[array_rand($this->roads)];
                    $address = "House {$houseNum}, {$roadType} {$roadNum}, {$areaName}";
                    
                    $connectionDate = Carbon::now()->subMonths(rand(1, 24))->subDays(rand(0, 28));
                    $status = $this->getRandomStatus();
                    
                    $clientId = 'CLT' . str_pad($clientNum + 1000, 6, '0', STR_PAD_LEFT);
                    $phone = '01' . rand(3, 9) . rand(10000000, 99999999);
                    
                    $client = Client::create([
                        'client_id' => $clientId,
                        'name' => $name,
                        'phone' => $phone,
                        'email' => strtolower(str_replace(' ', '.', $name)) . $clientNum . '@example.com',
                        'address' => $address,
                        'zone_id' => $zone->id,
                        'area_id' => $area->id,
                        'package_id' => $package->id,
                        'monthly_fee' => $package->price,
                        'connection_date' => $connectionDate,
                        'billing_date' => rand(1, 28),
                        'status' => $status,
                        'connection_type' => $this->getConnectionType(),
                        'username' => strtolower(str_replace(' ', '', $name)) . $clientNum,
                        'pppoe_password' => 'pass' . rand(1000, 9999),
                        'balance' => 0,
                        'due_amount' => 0,
                        'nid' => rand(1000000000, 9999999999) . rand(1000, 9999),
                        'father_husband_name' => $this->firstNames[array_rand($this->firstNames)] . ' ' . $this->lastNames[array_rand($this->lastNames)],
                        'created_at' => $connectionDate,
                        'updated_at' => now(),
                    ]);

                    $this->generateBillsAndPayments($client, $package, $paymentMethods, $admin, $technicians, $connectionDate);
                }
                
                $this->command->info("Batch " . ($batch + 1) . " of " . ($clientCount / $batchSize) . " completed...");
            }
            
            DB::commit();
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            $this->command->info("Successfully created {$clientCount} clients with bills and payments in {$duration} seconds!");
            $this->command->info("Total Clients: " . Client::count());
            $this->command->info("Total Bills: " . Bill::count());
            $this->command->info("Total Payments: " . Payment::count());
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getRandomStatus(): string
    {
        $statuses = ['active', 'active', 'active', 'active', 'active', 'active', 'active', 'inactive', 'inactive', 'pending'];
        return $statuses[array_rand($statuses)];
    }

    private function getConnectionType(): string
    {
        $types = ['pppoe', 'pppoe', 'pppoe', 'queue', 'invoice'];
        return $types[array_rand($types)];
    }

    private function generateBillsAndPayments($client, $package, $paymentMethods, $admin, $technicians, $connectionDate)
    {
        $monthsActive = Carbon::now()->diffInMonths($connectionDate);
        $monthsToGenerate = min($monthsActive, 12);
        
        $totalBillAmount = 0;
        $totalPaidAmount = 0;
        
        for ($m = $monthsToGenerate; $m >= 0; $m--) {
            $billDate = Carbon::now()->subMonths($m)->startOfMonth();
            
            if ($billDate->lt($connectionDate)) continue;
            
            $billAmount = $package->price;
            $totalBillAmount += $billAmount;
            
            $isPaid = ($m > 0) ? (rand(1, 100) <= 85) : (rand(1, 100) <= 50);
            $paidAmount = $isPaid ? $billAmount : (rand(0, 1) ? rand(0, $billAmount) : 0);
            
            $billNo = 'BILL' . $client->id . '-' . $billDate->format('Ym');
            
            $bill = Bill::create([
                'bill_no' => $billNo,
                'client_id' => $client->id,
                'billing_month' => $billDate->format('m'),
                'billing_year' => $billDate->format('Y'),
                'bill_date' => $billDate,
                'due_date' => $billDate->copy()->addDays(15),
                'package_amount' => $billAmount,
                'previous_due' => 0,
                'adjustment' => 0,
                'discount' => 0,
                'total_amount' => $billAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $billAmount - $paidAmount,
                'status' => $paidAmount >= $billAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                'generated_by' => $admin->id ?? 1,
                'created_at' => $billDate,
                'updated_at' => $billDate,
            ]);
            
            if ($paidAmount > 0) {
                $paymentDate = $billDate->copy()->addDays(rand(1, 25));
                $collector = $technicians->isNotEmpty() && rand(0, 1) ? $technicians->random() : $admin;
                
                $totalPaidAmount += $paidAmount;
                
                Payment::create([
                    'client_id' => $client->id,
                    'bill_id' => $bill->id,
                    'amount' => $paidAmount,
                    'payment_date' => $paymentDate,
                    'payment_method_id' => $paymentMethods->random()->id,
                    'collected_by' => $collector->id ?? 1,
                    'received_by' => $admin->id ?? 1,
                    'status' => 'approved',
                    'notes' => 'Monthly bill payment',
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]);
            }
        }
        
        $due = max(0, $totalBillAmount - $totalPaidAmount);
        $advance = max(0, $totalPaidAmount - $totalBillAmount);
        
        $client->update([
            'due_amount' => $due,
            'balance' => $advance,
        ]);
    }
}
