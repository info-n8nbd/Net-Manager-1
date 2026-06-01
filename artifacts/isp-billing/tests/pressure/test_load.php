<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  ISP BILLING SYSTEM - LOAD & PERFORMANCE TESTS         ║\n";
echo "║  Phase 4: Automated Pressure Testing                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$admin = User::where('user_type', 'admin')->first();
auth()->login($admin);

$billingService = app(BillingService::class);
$paymentMethod = PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Cash', 'is_active' => true]);

$results = [
    'tests' => [],
    'performance' => [],
    'bottlenecks' => [],
];

function benchmark(string $name, callable $fn, int $iterations = 1): array {
    $times = [];
    $errors = 0;
    for ($i = 0; $i < $iterations; $i++) {
        $start = microtime(true);
        try {
            $fn();
        } catch (\Exception $e) {
            $errors++;
        }
        $times[] = (microtime(true) - $start) * 1000;
    }
    sort($times);
    $avg = array_sum($times) / count($times);
    $p50 = $times[(int)(count($times) * 0.5)];
    $p95 = $times[(int)(count($times) * 0.95)];
    $p99 = $times[min(count($times) - 1, (int)(count($times) * 0.99))];
    return [
        'name' => $name,
        'iterations' => $iterations,
        'avg_ms' => round($avg, 2),
        'p50_ms' => round($p50, 2),
        'p95_ms' => round($p95, 2),
        'p99_ms' => round($p99, 2),
        'min_ms' => round(min($times), 2),
        'max_ms' => round(max($times), 2),
        'errors' => $errors,
        'ops_per_sec' => round(1000 / max($avg, 0.01), 1),
    ];
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "BENCHMARK 1: DATABASE READ OPERATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$b1 = benchmark("Client list query (paginated)", function() {
    Client::with(['zone', 'area', 'package'])
        ->where('status', 'active')
        ->orderBy('name')
        ->paginate(50);
}, 50);
$results['performance'][] = $b1;
echo "  Client list (50pg): avg={$b1['avg_ms']}ms, p95={$b1['p95_ms']}ms, {$b1['ops_per_sec']} ops/s\n";

$b2 = benchmark("Client search by name", function() {
    Client::where('name', 'LIKE', '%Test Client 1%')
        ->with(['package', 'zone'])
        ->limit(20)
        ->get();
}, 50);
$results['performance'][] = $b2;
echo "  Client search: avg={$b2['avg_ms']}ms, p95={$b2['p95_ms']}ms, {$b2['ops_per_sec']} ops/s\n";

$b3 = benchmark("Bill list with relationships", function() {
    Bill::with(['client', 'client.package', 'payments'])
        ->orderByDesc('created_at')
        ->paginate(50);
}, 50);
$results['performance'][] = $b3;
echo "  Bill list: avg={$b3['avg_ms']}ms, p95={$b3['p95_ms']}ms, {$b3['ops_per_sec']} ops/s\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "BENCHMARK 2: BILLING OPERATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$uniqueMonth = 12;
$uniqueYear = 2030;
$billClients = Client::where('username', 'LIKE', 'pt_client_%')
    ->where('status', 'active')
    ->limit(100)
    ->get();

$billIdx = 0;
$b4 = benchmark("Single bill generation", function() use ($billingService, $billClients, &$billIdx, $uniqueMonth, $uniqueYear) {
    if ($billIdx >= $billClients->count()) return;
    $client = $billClients[$billIdx];
    Bill::where('client_id', $client->id)
        ->where('billing_month', $uniqueMonth)
        ->where('billing_year', $uniqueYear)
        ->delete();
    $billingService->generateBill($client, $uniqueMonth, $uniqueYear);
    $billIdx++;
}, min(100, $billClients->count()));
$results['performance'][] = $b4;
echo "  Single bill gen: avg={$b4['avg_ms']}ms, p95={$b4['p95_ms']}ms, {$b4['ops_per_sec']} ops/s\n";

Bill::where('billing_month', $uniqueMonth)->where('billing_year', $uniqueYear)->delete();

$b5 = benchmark("Balance recalculation", function() use ($billingService, $billClients) {
    $client = $billClients->random();
    $billingService->recalculateClientBalance($client);
}, 100);
$results['performance'][] = $b5;
echo "  Balance recalc: avg={$b5['avg_ms']}ms, p95={$b5['p95_ms']}ms, {$b5['ops_per_sec']} ops/s\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "BENCHMARK 3: PAYMENT PROCESSING\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$unpaidBills = Bill::where('status', 'unpaid')
    ->where('bill_no', 'LIKE', 'PT-BILL-%')
    ->limit(50)
    ->get();

$payIdx = 0;
$b6 = benchmark("Process single payment", function() use ($billingService, $unpaidBills, &$payIdx, $paymentMethod, $admin) {
    if ($payIdx >= $unpaidBills->count()) return;
    $bill = $unpaidBills[$payIdx]->fresh();
    if ($bill && $bill->status !== 'paid') {
        $billingService->processPayment($bill, $bill->due_amount, $paymentMethod->id, $admin->id);
    }
    $payIdx++;
}, min(50, $unpaidBills->count()));
$results['performance'][] = $b6;
echo "  Single payment: avg={$b6['avg_ms']}ms, p95={$b6['p95_ms']}ms, {$b6['ops_per_sec']} ops/s\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "BENCHMARK 4: HEAVY REPORT QUERIES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$b7 = benchmark("Revenue summary (aggregated)", function() {
    DB::select("
        SELECT
            billing_month, billing_year,
            COUNT(*) as bill_count,
            SUM(total_amount) as total_billed,
            SUM(paid_amount) as total_collected,
            SUM(due_amount) as total_due
        FROM bills
        WHERE is_cancelled = false
        GROUP BY billing_year, billing_month
        ORDER BY billing_year DESC, billing_month DESC
    ");
}, 30);
$results['performance'][] = $b7;
echo "  Revenue summary: avg={$b7['avg_ms']}ms, p95={$b7['p95_ms']}ms\n";

$b8 = benchmark("Due client report (with joins)", function() {
    DB::select("
        SELECT c.id, c.name, c.phone, c.client_id,
               p.name as package_name,
               SUM(b.due_amount) as total_due,
               COUNT(b.id) as unpaid_bills
        FROM clients c
        JOIN packages p ON c.package_id = p.id
        JOIN bills b ON b.client_id = c.id AND b.status IN ('unpaid', 'partial') AND b.is_cancelled = false
        WHERE c.status = 'active'
        GROUP BY c.id, c.name, c.phone, c.client_id, p.name
        HAVING SUM(b.due_amount) > 0
        ORDER BY total_due DESC
        LIMIT 100
    ");
}, 30);
$results['performance'][] = $b8;
echo "  Due client report: avg={$b8['avg_ms']}ms, p95={$b8['p95_ms']}ms\n";

$b9 = benchmark("Collection report (cross-join)", function() {
    DB::select("
        SELECT 
            u.name as collector,
            COUNT(p.id) as payment_count,
            SUM(p.amount) as total_collected
        FROM payments p
        JOIN users u ON p.collected_by = u.id
        WHERE p.status = 'approved'
        AND p.payment_date >= CURRENT_DATE - INTERVAL '30 days'
        GROUP BY u.id, u.name
        ORDER BY total_collected DESC
    ");
}, 30);
$results['performance'][] = $b9;
echo "  Collection report: avg={$b9['avg_ms']}ms, p95={$b9['p95_ms']}ms\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "BENCHMARK 5: DATABASE INDEX EFFECTIVENESS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$indexChecks = [
    'clients.status lookup' => "SELECT COUNT(*) FROM clients WHERE status = 'active'",
    'bills.client_id+month' => "SELECT * FROM bills WHERE client_id = 1 AND billing_month = 1 AND billing_year = 2026 LIMIT 1",
    'payments.bill_id' => "SELECT * FROM payments WHERE bill_id = 1 LIMIT 1",
    'payments.client_id+status' => "SELECT SUM(amount) FROM payments WHERE client_id = 1 AND status = 'approved'",
];

foreach ($indexChecks as $label => $query) {
    $plan = DB::select("EXPLAIN ANALYZE " . $query);
    $planText = implode(' ', array_map(fn($r) => $r->{'QUERY PLAN'}, $plan));
    $hasSeqScan = str_contains($planText, 'Seq Scan') && !str_contains($planText, 'Index');
    $timing = 'N/A';
    if (preg_match('/Execution Time: ([\d.]+) ms/', $planText, $m)) {
        $timing = $m[1] . 'ms';
    }
    $icon = $hasSeqScan ? '⚠️' : '✅';
    echo "  {$icon} {$label}: {$timing}" . ($hasSeqScan ? " [NEEDS INDEX]" : " [Indexed]") . "\n";
    if ($hasSeqScan) {
        $results['bottlenecks'][] = "Missing index for: {$label}";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "BENCHMARK 6: HTTP ENDPOINT LOAD TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$endpoints = [
    '/' => 'Homepage',
    '/login' => 'Login Page',
    '/health' => 'Health Check',
    '/check' => 'Self-Service Portal',
];

foreach ($endpoints as $path => $label) {
    $times = [];
    $statusCodes = [];
    for ($i = 0; $i < 50; $i++) {
        $ch = curl_init("http://127.0.0.1:8000{$path}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $start = microtime(true);
        curl_exec($ch);
        $times[] = (microtime(true) - $start) * 1000;
        $statusCodes[] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    sort($times);
    $avg = round(array_sum($times) / count($times), 1);
    $p95 = round($times[(int)(count($times) * 0.95)], 1);
    $successRate = round(count(array_filter($statusCodes, fn($c) => $c >= 200 && $c < 400)) / count($statusCodes) * 100, 1);
    $icon = ($avg < 500 && $successRate >= 99) ? '✅' : '⚠️';
    echo "  {$icon} {$label} ({$path}): avg={$avg}ms, p95={$p95}ms, success={$successRate}%\n";
    $results['performance'][] = [
        'name' => "HTTP {$label}",
        'avg_ms' => $avg,
        'p95_ms' => $p95,
        'success_rate' => $successRate,
    ];
}

echo "\n\n╔══════════════════════════════════════════════════════════╗\n";
echo "║            PERFORMANCE TEST SUMMARY                     ║\n";
echo "╠══════════════════════════════════════════════════════════╣\n";
echo "║  Operation              │ Avg (ms) │ P95 (ms) │ Ops/s  ║\n";
echo "║─────────────────────────┼──────────┼──────────┼────────║\n";

foreach ($results['performance'] as $p) {
    if (!isset($p['ops_per_sec'])) continue;
    $name = str_pad(substr($p['name'], 0, 23), 23);
    $avg = str_pad(number_format($p['avg_ms'], 1), 8, ' ', STR_PAD_LEFT);
    $p95 = str_pad(number_format($p['p95_ms'], 1), 8, ' ', STR_PAD_LEFT);
    $ops = str_pad(number_format($p['ops_per_sec'], 0), 6, ' ', STR_PAD_LEFT);
    echo "║  {$name} │{$avg} │{$p95} │{$ops} ║\n";
}

echo "╚══════════════════════════════════════════════════════════╝\n";

if (count($results['bottlenecks']) > 0) {
    echo "\n⚠️  BOTTLENECKS IDENTIFIED:\n";
    foreach ($results['bottlenecks'] as $idx => $b) {
        echo "  " . ($idx + 1) . ". {$b}\n";
    }
}

file_put_contents(__DIR__ . '/results_load.json', json_encode($results, JSON_PRETTY_PRINT));
echo "\nResults saved to tests/pressure/results_load.json\n";
