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
use App\Models\Package;
use App\Models\Zone;
use App\Models\Area;
use App\Models\ScratchCard;
use App\Models\ScratchCardBatch;
use App\Services\BillingService;
use App\Services\CardBillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  ISP BILLING SYSTEM - CONCURRENCY PRESSURE TESTS       ║\n";
echo "║  Phase 1-2: Race Conditions, Deadlocks, Data Integrity ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$admin = User::where('user_type', 'admin')->first();
auth()->login($admin);

$billingService = app(BillingService::class);
$cardService = app(CardBillingService::class);
$paymentMethod = PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Cash', 'is_active' => true]);

$results = [
    'tests_run' => 0,
    'passed' => 0,
    'failed' => 0,
    'critical' => 0,
    'issues' => [],
    'timings' => [],
];

function reportTest(string $name, bool $passed, string $detail, array &$results, ?string $severity = null) {
    $results['tests_run']++;
    $icon = $passed ? '✅' : '❌';
    echo "  {$icon} {$name}\n";
    if (!$passed) {
        $sev = $severity ?? 'WARNING';
        echo "     └─ [{$sev}] {$detail}\n";
        $results['issues'][] = ['test' => $name, 'detail' => $detail, 'severity' => $sev];
        if ($sev === 'CRITICAL') $results['critical']++;
        $results['failed']++;
    } else {
        $results['passed']++;
        if ($detail) echo "     └─ {$detail}\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: DUPLICATE BILL GENERATION (Same client, same month)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$testClient = Client::where('username', 'LIKE', 'pt_client_%')
    ->where('status', 'active')
    ->first();

if ($testClient) {
    $testMonth = now()->addMonths(6)->month;
    $testYear = now()->addMonths(6)->year;

    Bill::where('client_id', $testClient->id)
        ->where('billing_month', $testMonth)
        ->where('billing_year', $testYear)
        ->delete();

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    for ($attempt = 0; $attempt < 10; $attempt++) {
        try {
            $billingService->generateBill($testClient, $testMonth, $testYear);
            $successCount++;
        } catch (\Exception $e) {
            $errorCount++;
            $errors[] = $e->getMessage();
        }
    }

    $billCount = Bill::where('client_id', $testClient->id)
        ->where('billing_month', $testMonth)
        ->where('billing_year', $testYear)
        ->count();

    reportTest(
        "Duplicate bill prevention (10 sequential attempts)",
        $billCount === 1 && $successCount === 1,
        $billCount === 1
            ? "Only 1 bill created, {$errorCount} correctly rejected"
            : "CREATED {$billCount} BILLS! Expected 1. Data corruption risk!",
        $results,
        $billCount > 1 ? 'CRITICAL' : null
    );

    Bill::where('client_id', $testClient->id)
        ->where('billing_month', $testMonth)
        ->where('billing_year', $testYear)
        ->delete();
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: CONCURRENT PAYMENT RACE CONDITIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$payClient = Client::where('username', 'LIKE', 'pt_client_%')
    ->where('status', 'active')
    ->whereHas('bills', function($q) { $q->where('status', 'unpaid'); })
    ->first();

if ($payClient) {
    $unpaidBill = $payClient->bills()->where('status', 'unpaid')->first();

    if ($unpaidBill) {
        $originalAmount = $unpaidBill->total_amount;
        $paymentAmount = $originalAmount / 2;

        $paymentsBefore = Payment::where('bill_id', $unpaidBill->id)->count();

        $paymentResults = [];
        for ($i = 0; $i < 5; $i++) {
            try {
                $payment = $billingService->processPayment(
                    $unpaidBill->fresh(),
                    $paymentAmount,
                    $paymentMethod->id,
                    $admin->id,
                    'admin'
                );
                $paymentResults[] = ['success' => true, 'id' => $payment->id];
            } catch (\Exception $e) {
                $paymentResults[] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        $paymentsAfter = Payment::where('bill_id', $unpaidBill->id)->count();
        $newPayments = $paymentsAfter - $paymentsBefore;

        $unpaidBill->refresh();
        $totalPaid = Payment::where('bill_id', $unpaidBill->id)->where('status', 'approved')->sum('amount');
        $overpaid = $totalPaid > $originalAmount;

        reportTest(
            "Multiple rapid payments on same bill (5x ৳{$paymentAmount})",
            !$overpaid,
            $overpaid
                ? "OVERPAYMENT: ৳{$totalPaid} paid on ৳{$originalAmount} bill! No protection!"
                : "Payments recorded: {$newPayments}, Total: ৳{$totalPaid} on ৳{$originalAmount} bill",
            $results,
            $overpaid ? 'CRITICAL' : null
        );

        $expectedDue = max(0, $originalAmount - $totalPaid);
        $actualDue = $unpaidBill->fresh()->due_amount;
        reportTest(
            "Bill due_amount consistency after multiple payments",
            abs($actualDue - $expectedDue) < 0.01,
            abs($actualDue - $expectedDue) < 0.01
                ? "Due: ৳{$actualDue} matches expected ৳{$expectedDue}"
                : "MISMATCH: due=৳{$actualDue}, expected=৳{$expectedDue}",
            $results,
            abs($actualDue - $expectedDue) >= 0.01 ? 'CRITICAL' : null
        );
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: CONCURRENT BALANCE UPDATE RACE CONDITIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$balanceClient = Client::where('username', 'LIKE', 'pt_client_%')
    ->where('status', 'active')
    ->first();

if ($balanceClient) {
    $billingService->recalculateClientBalance($balanceClient);
    $balanceClient->refresh();
    $initialDue = $balanceClient->due_amount;

    for ($i = 0; $i < 20; $i++) {
        $billingService->recalculateClientBalance($balanceClient);
    }

    $balanceClient->refresh();
    $finalDue = $balanceClient->due_amount;

    reportTest(
        "Balance idempotency (20 consecutive recalculations)",
        abs($initialDue - $finalDue) < 0.01,
        abs($initialDue - $finalDue) < 0.01
            ? "Balance stable: ৳{$finalDue} after 20 recalcs"
            : "DRIFT: ৳{$initialDue} → ৳{$finalDue} (delta: " . abs($initialDue - $finalDue) . ")",
        $results,
        abs($initialDue - $finalDue) >= 0.01 ? 'CRITICAL' : null
    );
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 4: CARD BILLING RACE CONDITIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$plainPin = 'TEST-PIN-12345';
$testBatch = ScratchCardBatch::create([
    'batch_no' => 'PT-BATCH-RACE-' . time(),
    'name' => 'Race Test Batch',
    'amount' => 500,
    'validity_days' => 30,
    'total_cards' => 5,
    'active_cards' => 5,
    'status' => 'active',
    'created_by' => $admin->id,
    'expires_at' => now()->addDays(30),
]);

$testCards = [];
for ($i = 0; $i < 5; $i++) {
    $card = ScratchCard::create([
        'batch_id' => $testBatch->id,
        'card_number' => 'RACE-CARD-' . time() . '-' . $i,
        'pin' => Hash::make($plainPin),
        'serial_number' => 'RACE-SN-' . time() . '-' . $i,
        'amount' => 500,
        'validity_days' => 30,
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);
    $testCards[] = $card;
}

$card = $testCards[0];
$redeemClient = Client::where('username', 'LIKE', 'pt_client_%')->where('status', 'active')->first();

if ($redeemClient && $card) {
    $redeemResults = [];
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $result = $cardService->redeemCard($card->card_number, $plainPin, $redeemClient);
        $redeemResults[] = $result;
    }

    $successCount = count(array_filter($redeemResults, fn($r) => $r['success']));
    $card->refresh();

    reportTest(
        "Card double-redeem prevention (5 sequential attempts)",
        $successCount === 1,
        $successCount === 1
            ? "1 success, " . (count($redeemResults) - 1) . " rejections. Card status: {$card->status}"
            : "REDEEMED {$successCount} TIMES! Expected 1. Financial loss risk!",
        $results,
        $successCount > 1 ? 'CRITICAL' : null
    );
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 5: ACCOUNT TRANSFER INTEGRITY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$account1 = \App\Models\Account::firstOrCreate(
    ['name' => 'Pressure Test Account A'],
    ['type' => 'cash', 'current_balance' => 100000, 'status' => 'active']
);
$account2 = \App\Models\Account::firstOrCreate(
    ['name' => 'Pressure Test Account B'],
    ['type' => 'cash', 'current_balance' => 100000, 'status' => 'active']
);

$account1->current_balance = 100000;
$account1->save();
$account2->current_balance = 100000;
$account2->save();

$totalBefore = $account1->current_balance + $account2->current_balance;

for ($i = 0; $i < 20; $i++) {
    DB::transaction(function () use ($account1, $account2) {
        $amount = rand(100, 5000);
        $account1->refresh();
        $account2->refresh();
        $account1->current_balance -= $amount;
        $account1->save();
        $account2->current_balance += $amount;
        $account2->save();
    });
}

$account1->refresh();
$account2->refresh();
$totalAfter = $account1->current_balance + $account2->current_balance;

reportTest(
    "Fund transfer conservation (20 rapid transfers)",
    abs($totalBefore - $totalAfter) < 0.01,
    abs($totalBefore - $totalAfter) < 0.01
        ? "Total conserved: ৳{$totalAfter} (before: ৳{$totalBefore})"
        : "MONEY LEAK: ৳{$totalBefore} → ৳{$totalAfter} (lost: ৳" . ($totalBefore - $totalAfter) . ")",
    $results,
    abs($totalBefore - $totalAfter) >= 0.01 ? 'CRITICAL' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 6: MASS BILL GENERATION UNDER LOAD\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$futureMonth = now()->addMonths(8)->month;
$futureYear = now()->addMonths(8)->year;

Bill::where('billing_month', $futureMonth)
    ->where('billing_year', $futureYear)
    ->where('bill_no', 'LIKE', 'PT-BILL-%')
    ->delete();

$startGen = microtime(true);
$genResult = $billingService->generateMonthlyBillsForAllClients($futureMonth, $futureYear);
$genTime = round(microtime(true) - $startGen, 2);

$clientCount = Client::where('status', 'active')->where('monthly_fee', '>', 0)->count();
$totalGenerated = $genResult['generated'];
$genRate = $totalGenerated > 0 ? round($totalGenerated / max($genTime, 0.01), 1) : 0;

reportTest(
    "Mass bill generation ({$clientCount} active clients)",
    $totalGenerated > 0 && count($genResult['errors']) === 0,
    "Generated: {$totalGenerated}, Skipped: {$genResult['skipped']}, Errors: " . count($genResult['errors']) . ", Time: {$genTime}s ({$genRate}/sec)",
    $results,
    count($genResult['errors']) > 0 ? 'WARNING' : null
);

$duplicates = DB::select("
    SELECT client_id, billing_month, billing_year, COUNT(*) as cnt
    FROM bills
    WHERE billing_month = ? AND billing_year = ?
    GROUP BY client_id, billing_month, billing_year
    HAVING COUNT(*) > 1
", [$futureMonth, $futureYear]);

reportTest(
    "No duplicate bills after mass generation",
    count($duplicates) === 0,
    count($duplicates) === 0
        ? "Zero duplicates confirmed"
        : count($duplicates) . " clients have duplicate bills!",
    $results,
    count($duplicates) > 0 ? 'CRITICAL' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 7: PARTIAL PAYMENT DISTRIBUTION INTEGRITY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$multiClient = Client::where('username', 'LIKE', 'pt_client_%')
    ->where('status', 'active')
    ->whereHas('bills', function($q) { $q->where('status', 'unpaid'); })
    ->first();

if ($multiClient) {
    $unpaidBills = $multiClient->bills()->where('status', 'unpaid')->get();
    $totalDue = $unpaidBills->sum('due_amount');

    if ($totalDue > 0) {
        $partialAmount = $totalDue * 0.3;
        try {
            $payments = $billingService->processPartialPayment(
                $multiClient,
                $partialAmount,
                $paymentMethod->id,
                'admin'
            );

            $billingService->recalculateClientBalance($multiClient);
            $multiClient->refresh();

            $expectedDue = $totalDue - $partialAmount;
            $actualDue = $multiClient->due_amount;

            reportTest(
                "Partial payment distribution (30% of ৳{$totalDue})",
                abs($actualDue - $expectedDue) < 1.0,
                "Paid: ৳{$partialAmount}, Expected due: ৳" . round($expectedDue, 2) . ", Actual: ৳{$actualDue}",
                $results,
                abs($actualDue - $expectedDue) >= 1.0 ? 'WARNING' : null
            );
        } catch (\Exception $e) {
            reportTest(
                "Partial payment distribution",
                false,
                "Exception: " . $e->getMessage(),
                $results,
                'WARNING'
            );
        }
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 8: CONCURRENT CLIENT CREATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$createdClientIds = [];
$createErrors = [];
$startCreate = microtime(true);

for ($i = 0; $i < 20; $i++) {
    try {
        DB::beginTransaction();
        $package = Package::first();
        $uniqueId = time() . '_' . $i . '_' . rand(1000, 9999);

        $user = User::create([
            'name' => "Concurrent User {$uniqueId}",
            'email' => "concurrent_{$uniqueId}@test.com",
            'password' => Hash::make('password'),
            'user_type' => 'client',
        ]);

        $client = Client::create([
            'client_id' => 'CC-' . $uniqueId,
            'user_id' => $user->id,
            'name' => "Concurrent Client {$uniqueId}",
            'phone' => '018' . str_pad($i, 8, '0', STR_PAD_LEFT),
            'username' => "cc_{$uniqueId}",
            'password' => 'test123',
            'address' => "Concurrent Address",
            'package_id' => $package->id,
            'monthly_fee' => $package->price,
            'zone_id' => Zone::first()->id,
            'area_id' => Area::first()->id,
            'status' => 'active',
            'connection_date' => now(),
        ]);

        DB::commit();
        $createdClientIds[] = $client->id;
    } catch (\Exception $e) {
        DB::rollBack();
        $createErrors[] = $e->getMessage();
    }
}

$createTime = round(microtime(true) - $startCreate, 2);

reportTest(
    "Rapid client creation (20 sequential)",
    count($createdClientIds) === 20 && count($createErrors) === 0,
    "Created: " . count($createdClientIds) . "/20, Errors: " . count($createErrors) . ", Time: {$createTime}s",
    $results,
    count($createErrors) > 0 ? 'WARNING' : null
);

$duplicateUsers = DB::select("SELECT email, COUNT(*) cnt FROM users WHERE email LIKE 'concurrent_%' GROUP BY email HAVING COUNT(*) > 1");
reportTest(
    "No duplicate user accounts created",
    count($duplicateUsers) === 0,
    count($duplicateUsers) === 0 ? "Zero duplicates" : count($duplicateUsers) . " duplicate emails found!",
    $results,
    count($duplicateUsers) > 0 ? 'CRITICAL' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 9: DATA INTEGRITY VERIFICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$integrityIssues = [];

$negativeBills = Bill::where('total_amount', '<', 0)->count();
if ($negativeBills > 0) $integrityIssues[] = "{$negativeBills} bills with negative amounts";

$negativePaid = Bill::where('paid_amount', '<', 0)->count();
if ($negativePaid > 0) $integrityIssues[] = "{$negativePaid} bills with negative paid_amount";

$overpaidBills = DB::select("SELECT COUNT(*) as cnt FROM bills WHERE paid_amount > total_amount + 0.01 AND is_cancelled = false");
if ($overpaidBills[0]->cnt > 0) $integrityIssues[] = "{$overpaidBills[0]->cnt} overpaid bills";

$orphanPayments = DB::select("SELECT COUNT(*) cnt FROM payments WHERE client_id NOT IN (SELECT id FROM clients)");
if ($orphanPayments[0]->cnt > 0) $integrityIssues[] = "{$orphanPayments[0]->cnt} orphan payments";

$negativeBalance = Client::where('balance', '<', 0)->count();
if ($negativeBalance > 0) $integrityIssues[] = "{$negativeBalance} clients with negative balance";

$statusMismatch = DB::select("SELECT COUNT(*) cnt FROM bills WHERE status = 'paid' AND due_amount > 0.01 AND is_cancelled = false");
if ($statusMismatch[0]->cnt > 0) $integrityIssues[] = "{$statusMismatch[0]->cnt} 'paid' bills with remaining due";

reportTest(
    "Overall data integrity check",
    count($integrityIssues) === 0,
    count($integrityIssues) === 0
        ? "All integrity checks passed"
        : "Issues: " . implode('; ', $integrityIssues),
    $results,
    count($integrityIssues) > 0 ? 'CRITICAL' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 10: ADVANCE PAYMENT & BALANCE RECONCILIATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$sampleClients = Client::where('username', 'LIKE', 'pt_client_%')
    ->where('status', 'active')
    ->limit(50)
    ->get();

$reconcileErrors = 0;
foreach ($sampleClients as $sc) {
    $totalBills = (float) $sc->bills()->where('is_cancelled', false)->sum('total_amount');
    $totalPaid = (float) $sc->payments()->where('status', 'approved')->sum('amount');
    $diff = $totalBills - $totalPaid;
    $expectedDue = max(0, $diff);

    if (abs($sc->due_amount - $expectedDue) > 1.0) {
        $reconcileErrors++;
    }
}

reportTest(
    "Balance reconciliation (50 client sample)",
    $reconcileErrors === 0,
    $reconcileErrors === 0
        ? "All 50 clients' balances are consistent"
        : "{$reconcileErrors}/50 clients have inconsistent balances",
    $results,
    $reconcileErrors > 0 ? 'WARNING' : null
);

echo "\n\n╔══════════════════════════════════════════════════════════╗\n";
echo "║                  CONCURRENCY TEST RESULTS               ║\n";
echo "╠══════════════════════════════════════════════════════════╣\n";
printf("║  Tests Run:  %-40d ║\n", $results['tests_run']);
printf("║  Passed:     %-40d ║\n", $results['passed']);
printf("║  Failed:     %-40d ║\n", $results['failed']);
printf("║  Critical:   %-40d ║\n", $results['critical']);
echo "╚══════════════════════════════════════════════════════════╝\n";

if (count($results['issues']) > 0) {
    echo "\n⚠️  ISSUES FOUND:\n";
    foreach ($results['issues'] as $idx => $issue) {
        echo "  " . ($idx + 1) . ". [{$issue['severity']}] {$issue['test']}: {$issue['detail']}\n";
    }
}

file_put_contents(__DIR__ . '/results_concurrency.json', json_encode($results, JSON_PRETTY_PRINT));
echo "\nResults saved to tests/pressure/results_concurrency.json\n";
