<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Package;
use App\Models\Zone;
use App\Models\Area;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== ISP BILLING SYSTEM - PRESSURE TEST DATA SEEDER ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

$targetClients = (int)($argv[1] ?? 500);
echo "Target: {$targetClients} test clients\n";

$admin = User::where('user_type', 'admin')->first();
if (!$admin) {
    $admin = User::create([
        'name' => 'Pressure Test Admin',
        'email' => 'pressure_admin@test.com',
        'password' => Hash::make('password123'),
        'user_type' => 'admin',
    ]);
}
auth()->login($admin);

$zone = Zone::firstOrCreate(['name' => 'Test Zone'], [
    'description' => 'Pressure test zone',
    'status' => 'active',
]);

$area = Area::firstOrCreate(['name' => 'Test Area'], [
    'zone_id' => $zone->id,
    'description' => 'Pressure test area',
    'status' => 'active',
]);

$paymentMethod = PaymentMethod::firstOrCreate(['code' => 'cash'], [
    'name' => 'Cash',
    'is_active' => true,
]);

$packages = Package::all();
if ($packages->isEmpty()) {
    $packageData = [
        ['name' => 'Basic 10Mbps', 'speed' => '10', 'price' => 500],
        ['name' => 'Standard 20Mbps', 'speed' => '20', 'price' => 800],
        ['name' => 'Premium 50Mbps', 'speed' => '50', 'price' => 1200],
        ['name' => 'Ultra 100Mbps', 'speed' => '100', 'price' => 2000],
    ];
    foreach ($packageData as $p) {
        Package::create([
            'name' => $p['name'],
            'bandwidth' => $p['speed'],
            'price' => $p['price'],
            'status' => 'active',
        ]);
    }
    $packages = Package::all();
}

$existingTestClients = Client::where('username', 'LIKE', 'pt_client_%')->count();
$toCreate = max(0, $targetClients - $existingTestClients);

echo "Existing test clients: {$existingTestClients}\n";
echo "Creating: {$toCreate} new test clients\n\n";

$batchSize = 50;
$created = 0;
$startTime = microtime(true);

DB::beginTransaction();
try {
    for ($i = $existingTestClients + 1; $i <= $targetClients; $i++) {
        $package = $packages->random();

        $user = User::create([
            'name' => "Test Client {$i}",
            'email' => "pt_client_{$i}@test.com",
            'password' => Hash::make('password123'),
            'phone' => '017' . str_pad($i, 8, '0', STR_PAD_LEFT),
            'user_type' => 'client',
        ]);

        $client = Client::create([
            'client_id' => 'PT-' . str_pad($i, 5, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'name' => "Test Client {$i}",
            'phone' => '017' . str_pad($i, 8, '0', STR_PAD_LEFT),
            'email' => "pt_client_{$i}@test.com",
            'username' => "pt_client_{$i}",
            'password' => 'test123',
            'address' => "Address {$i}, Test Area",
            'package_id' => $package->id,
            'monthly_fee' => $package->price,
            'zone_id' => $zone->id,
            'area_id' => $area->id,
            'status' => 'active',
            'connection_date' => now()->subMonths(rand(1, 12)),
            'expire_date' => now()->addMonth(),
            'connection_type' => 'pppoe',
        ]);

        $months = rand(1, 3);
        for ($m = $months; $m >= 1; $m--) {
            $billMonth = now()->subMonths($m)->month;
            $billYear = now()->subMonths($m)->year;

            $bill = Bill::create([
                'bill_no' => 'PT-BILL-' . $i . '-' . $billMonth . '-' . $billYear,
                'client_id' => $client->id,
                'billing_month' => $billMonth,
                'billing_year' => $billYear,
                'bill_date' => now()->subMonths($m),
                'due_date' => now()->subMonths($m)->addDays(15),
                'package_amount' => $client->monthly_fee,
                'discount' => 0,
                'total_amount' => $client->monthly_fee,
                'paid_amount' => ($m > 1) ? $client->monthly_fee : 0,
                'due_amount' => ($m > 1) ? 0 : $client->monthly_fee,
                'status' => ($m > 1) ? 'paid' : 'unpaid',
                'generated_by' => $admin->id,
            ]);

            if ($m > 1) {
                Payment::create([
                    'payment_no' => 'PT-PAY-' . $i . '-' . $billMonth,
                    'client_id' => $client->id,
                    'bill_id' => $bill->id,
                    'amount' => $client->monthly_fee,
                    'payment_method_id' => $paymentMethod->id,
                    'payment_date' => now()->subMonths($m)->addDays(rand(1, 10)),
                    'collected_by' => $admin->id,
                    'status' => 'approved',
                ]);
            }
        }

        $client->due_amount = $client->monthly_fee;
        $client->save();

        $created++;

        if ($created % $batchSize === 0) {
            DB::commit();
            DB::beginTransaction();
            $elapsed = round(microtime(true) - $startTime, 1);
            $rate = round($created / max($elapsed, 0.1), 1);
            echo "  Created {$created}/{$toCreate} clients ({$rate}/sec)\n";
        }
    }
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
    exit(1);
}

$totalElapsed = round(microtime(true) - $startTime, 2);
$totalClients = Client::where('username', 'LIKE', 'pt_client_%')->count();
$totalBills = Bill::where('bill_no', 'LIKE', 'PT-BILL-%')->count();
$totalPayments = Payment::where('payment_no', 'LIKE', 'PT-PAY-%')->count();

echo "\n=== SEEDING COMPLETE ===\n";
echo "Time: {$totalElapsed}s\n";
echo "Test Clients: {$totalClients}\n";
echo "Test Bills: {$totalBills}\n";
echo "Test Payments: {$totalPayments}\n";
echo "Rate: " . round($created / max($totalElapsed, 0.1), 1) . " clients/sec\n";
