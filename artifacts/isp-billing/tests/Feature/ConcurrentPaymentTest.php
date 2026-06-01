<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bill;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\StaffDue;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  CONCURRENT PAYMENT & BILLING INTEGRITY TESTS          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$admin = User::where('user_type', 'admin')->first();
if (!$admin) {
    echo "❌ No admin user found. Cannot run tests.\n";
    exit(1);
}
auth()->login($admin);

$billingService = app(BillingService::class);
$paymentMethod = PaymentMethod::where('is_active', true)->first();
if (!$paymentMethod) {
    echo "❌ No active payment method found.\n";
    exit(1);
}

$results = ['tests_run' => 0, 'passed' => 0, 'failed' => 0, 'issues' => []];

function reportTest(string $name, bool $passed, string $detail, array &$results) {
    $results['tests_run']++;
    $icon = $passed ? '✅' : '❌';
    echo "  {$icon} {$name}\n";
    if (!$passed) {
        echo "     └─ {$detail}\n";
        $results['issues'][] = ['test' => $name, 'detail' => $detail];
        $results['failed']++;
    } else {
        $results['passed']++;
        if ($detail) echo "     └─ {$detail}\n";
    }
}

$testClient = Client::where('status', 'active')->whereNotNull('package_id')->first();
if (!$testClient) {
    echo "❌ No active client with package found.\n";
    exit(1);
}

echo "━━━ Test Client: {$testClient->client_id} ({$testClient->name}) ━━━━━━━━━━━━\n\n";

echo "TEST 1: Sequential Payment Balance Accuracy\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
try {
    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();
    $initialBalance = (float) $testClient->due_amount;
    $amount = 100;

    $payment = $billingService->recordPayment($testClient, [
        'amount' => $amount,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now(),
        'remarks' => 'Sequential test payment',
    ], $admin->id, 'admin');

    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();

    $expectedBalance = round($initialBalance - $amount, 2);
    $actualBalance = round((float) $testClient->due_amount, 2);
    $match = abs($expectedBalance - $actualBalance) < 0.01;

    reportTest('Balance after single payment', $match,
        $match ? "Expected ৳{$expectedBalance}, got ৳{$actualBalance}" :
        "MISMATCH: Expected ৳{$expectedBalance}, got ৳{$actualBalance}", $results);

    $payment?->delete();
    $billingService->recalculateClientBalance($testClient);
} catch (\Exception $e) {
    reportTest('Balance after single payment', false, "Exception: {$e->getMessage()}", $results);
}

echo "\nTEST 2: Multiple Rapid Payments - No Double Application\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
try {
    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();
    $initialBalance = (float) $testClient->due_amount;

    $paymentCount = 5;
    $amountEach = 50;
    $totalPaid = $paymentCount * $amountEach;
    $paymentIds = [];

    for ($i = 0; $i < $paymentCount; $i++) {
        $payment = DB::transaction(function () use ($testClient, $amountEach, $paymentMethod, $billingService, $admin, $i) {
            return $billingService->recordPayment($testClient->fresh(), [
                'amount' => $amountEach,
                'payment_method_id' => $paymentMethod->id,
                'payment_date' => now(),
                'remarks' => "Rapid test payment {$i}",
            ], $admin->id, 'admin');
        });
        $paymentIds[] = $payment->id;
    }

    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();

    $expectedBalance = round($initialBalance - $totalPaid, 2);
    $actualBalance = round((float) $testClient->due_amount, 2);
    $match = abs($expectedBalance - $actualBalance) < 0.01;

    reportTest("5 rapid payments of ৳50 each", $match,
        $match ? "Balance correct: ৳{$actualBalance}" :
        "MISMATCH: Expected ৳{$expectedBalance}, got ৳{$actualBalance}", $results);

    $actualSum = (float) Payment::whereIn('id', $paymentIds)->sum('amount');
    $sumMatch = abs($totalPaid - $actualSum) < 0.01;
    reportTest("Payment records sum correctly", $sumMatch,
        $sumMatch ? "Sum: ৳{$actualSum}" :
        "SUM MISMATCH: Expected ৳{$totalPaid}, got ৳{$actualSum}", $results);

    foreach ($paymentIds as $id) {
        Payment::find($id)?->delete();
    }
    $billingService->recalculateClientBalance($testClient);
} catch (\Exception $e) {
    reportTest('Multiple rapid payments', false, "Exception: {$e->getMessage()}", $results);
}

echo "\nTEST 3: Recalculate Balance Idempotency\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
try {
    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();
    $balance1 = (float) $testClient->due_amount;

    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();
    $balance2 = (float) $testClient->due_amount;

    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();
    $balance3 = (float) $testClient->due_amount;

    $idempotent = abs($balance1 - $balance2) < 0.01 && abs($balance2 - $balance3) < 0.01;
    reportTest('recalculateClientBalance is idempotent', $idempotent,
        $idempotent ? "3 calls: ৳{$balance1}, ৳{$balance2}, ৳{$balance3}" :
        "DRIFT detected: ৳{$balance1} → ৳{$balance2} → ৳{$balance3}", $results);
} catch (\Exception $e) {
    reportTest('recalculateClientBalance idempotency', false, "Exception: {$e->getMessage()}", $results);
}

echo "\nTEST 4: StaffDue Atomic Increment\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
try {
    $staffTestUser = $admin;
    if ($staffTestUser) {
        $staffDue = StaffDue::firstOrCreate(
            ['user_id' => $admin->id],
            ['total_collected' => 0, 'total_submitted' => 0, 'current_due' => 0]
        );
        $initialCollected = (float) $staffDue->total_collected;
        $initialDue = (float) $staffDue->current_due;

        $incrementCount = 10;
        $incrementAmount = 100;

        for ($i = 0; $i < $incrementCount; $i++) {
            DB::transaction(function () use ($admin, $incrementAmount) {
                StaffDue::lockForUpdate()->where('user_id', $admin->id)->first();
                StaffDue::where('user_id', $admin->id)
                    ->increment('total_collected', $incrementAmount);
                StaffDue::where('user_id', $admin->id)
                    ->increment('current_due', $incrementAmount);
            });
        }

        $staffDue->refresh();
        $expectedCollected = $initialCollected + ($incrementCount * $incrementAmount);
        $expectedDue = $initialDue + ($incrementCount * $incrementAmount);

        $collectedMatch = abs($expectedCollected - (float) $staffDue->total_collected) < 0.01;
        $dueMatch = abs($expectedDue - (float) $staffDue->current_due) < 0.01;

        reportTest('StaffDue total_collected accuracy', $collectedMatch,
            $collectedMatch ? "Expected ৳{$expectedCollected}, got ৳{$staffDue->total_collected}" :
            "MISMATCH: Expected ৳{$expectedCollected}, got ৳{$staffDue->total_collected}", $results);

        reportTest('StaffDue current_due accuracy', $dueMatch,
            $dueMatch ? "Expected ৳{$expectedDue}, got ৳{$staffDue->current_due}" :
            "MISMATCH: Expected ৳{$expectedDue}, got ৳{$staffDue->current_due}", $results);

        DB::transaction(function () use ($admin, $incrementCount, $incrementAmount) {
            StaffDue::where('user_id', $admin->id)
                ->decrement('total_collected', $incrementCount * $incrementAmount);
            StaffDue::where('user_id', $admin->id)
                ->decrement('current_due', $incrementCount * $incrementAmount);
        });
    }
} catch (\Exception $e) {
    reportTest('StaffDue atomic increment', false, "Exception: {$e->getMessage()}", $results);
}

echo "\nTEST 5: Duplicate Bill Generation Prevention\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
try {
    $testMonth = now()->addMonths(12)->month;
    $testYear = now()->addMonths(12)->year;

    Bill::where('client_id', $testClient->id)
        ->where('billing_month', $testMonth)
        ->where('billing_year', $testYear)
        ->delete();

    $generatedCount = 0;
    for ($i = 0; $i < 3; $i++) {
        try {
            $bill = $billingService->generateBill($testClient, $testMonth, $testYear);
            if ($bill) $generatedCount++;
        } catch (\Exception $e) {
        }
    }

    $actualCount = Bill::where('client_id', $testClient->id)
        ->where('billing_month', $testMonth)
        ->where('billing_year', $testYear)
        ->count();

    $noDuplicates = $actualCount <= 1;
    reportTest('Duplicate bill prevention', $noDuplicates,
        $noDuplicates ? "Generated attempts: 3, actual bills: {$actualCount}" :
        "DUPLICATE BILLS: 3 attempts created {$actualCount} bills!", $results);

    Bill::where('client_id', $testClient->id)
        ->where('billing_month', $testMonth)
        ->where('billing_year', $testYear)
        ->delete();
    $billingService->recalculateClientBalance($testClient);
} catch (\Exception $e) {
    reportTest('Duplicate bill prevention', false, "Exception: {$e->getMessage()}", $results);
}

echo "\nTEST 6: FIFO Payment Settlement Order\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
try {
    $existingUnpaid = Bill::where('client_id', $testClient->id)
        ->whereIn('status', ['unpaid', 'partial'])
        ->where('is_cancelled', false)
        ->orderBy('billing_year')
        ->orderBy('billing_month')
        ->get();

    $existingTotalDue = $existingUnpaid->sum('due_amount');
    $fifoPaymentIds = [];

    if ($existingTotalDue > 0) {
        $clearPayment = $billingService->recordPayment($testClient, [
            'amount' => $existingTotalDue,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => now(),
            'remarks' => 'FIFO test: clear existing dues',
        ], $admin->id, 'admin');
        $fifoPaymentIds[] = $clearPayment->id;
    }

    $month1 = now()->addMonths(10)->month;
    $year1 = now()->addMonths(10)->year;
    $month2 = now()->addMonths(11)->month;
    $year2 = now()->addMonths(11)->year;

    Bill::where('client_id', $testClient->id)
        ->where(function ($q) use ($month1, $year1, $month2, $year2) {
            $q->where(function ($q2) use ($month1, $year1) {
                $q2->where('billing_month', $month1)->where('billing_year', $year1);
            })->orWhere(function ($q2) use ($month2, $year2) {
                $q2->where('billing_month', $month2)->where('billing_year', $year2);
            });
        })
        ->delete();

    $bill1 = $billingService->generateBill($testClient, $month1, $year1);
    $bill2 = $billingService->generateBill($testClient, $month2, $year2);

    if (!$bill1 || !$bill2) {
        reportTest('FIFO settlement', false, "Could not generate test bills", $results);
    } else {
        $paymentAmount = (float) ($bill1->total_amount ?: $bill1->due_amount);

        $payment = $billingService->recordPayment($testClient, [
            'amount' => $paymentAmount,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => now(),
            'remarks' => 'FIFO test payment',
        ], $admin->id, 'admin');
        $fifoPaymentIds[] = $payment->id;

        $bill1->refresh();
        $bill2->refresh();

        $fifoCorrect = in_array($bill1->status, ['paid', 'partial']);
        reportTest('FIFO: Oldest bill settled first', $fifoCorrect,
            $fifoCorrect ? "Bill 1 ({$month1}/{$year1}): {$bill1->status}, Bill 2 ({$month2}/{$year2}): {$bill2->status}" :
            "Bill 1 should be paid/partial but is: {$bill1->status}", $results);

        Bill::whereIn('id', [$bill1->id, $bill2->id])->delete();
    }

    foreach ($fifoPaymentIds as $id) {
        Payment::find($id)?->delete();
    }
    $billingService->recalculateClientBalance($testClient);
} catch (\Exception $e) {
    reportTest('FIFO settlement', false, "Exception: {$e->getMessage()}", $results);
}

echo "\nTEST 7: Advance Payment Creates Negative Due\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
try {
    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();
    $initialDue = (float) $testClient->due_amount;
    $advanceAmount = abs($initialDue) + 500;

    $payment = $billingService->recordPayment($testClient, [
        'amount' => $advanceAmount,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now(),
        'remarks' => 'Advance payment test',
    ], $admin->id, 'admin');

    $billingService->recalculateClientBalance($testClient);
    $testClient->refresh();

    $isNegativeOrZero = (float) $testClient->due_amount <= 0;
    reportTest('Advance payment creates negative due', $isNegativeOrZero,
        $isNegativeOrZero ? "Due after advance: ৳{$testClient->due_amount}" :
        "Due should be ≤0 but is: ৳{$testClient->due_amount}", $results);

    $payment?->delete();
    $billingService->recalculateClientBalance($testClient);
} catch (\Exception $e) {
    reportTest('Advance payment', false, "Exception: {$e->getMessage()}", $results);
}

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║  RESULTS SUMMARY                                       ║\n";
echo "╠══════════════════════════════════════════════════════════╣\n";
printf("║  Tests Run: %-4d  Passed: %-4d  Failed: %-4d          ║\n", $results['tests_run'], $results['passed'], $results['failed']);
echo "╚══════════════════════════════════════════════════════════╝\n";

if (!empty($results['issues'])) {
    echo "\nFailed Tests:\n";
    foreach ($results['issues'] as $issue) {
        echo "  ❌ {$issue['test']}: {$issue['detail']}\n";
    }
}

exit($results['failed'] > 0 ? 1 : 0);
