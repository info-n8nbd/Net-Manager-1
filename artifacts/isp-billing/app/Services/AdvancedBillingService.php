<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class AdvancedBillingService
{
    public function createBillSplit(int $billId, ?array $options = null): array
    {
        $bill = Bill::find($billId);
        if (!$bill) {
            return ['error' => 'Bill not found'];
        }

        $firstPayment = $options['first_amount'] ?? ($bill->total_amount / 2);
        $secondPayment = $bill->total_amount - $firstPayment;
        $secondDueDate = $options['second_due_date'] ?? now()->addDays(15);

        DB::table('bill_splits')->insert([
            'bill_id' => $billId,
            'client_id' => $bill->client_id,
            'company_id' => $bill->company_id,
            'first_payment' => $firstPayment,
            'second_payment' => $secondPayment,
            'first_due_date' => $bill->due_date,
            'second_due_date' => $secondDueDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'bill_id' => $billId,
            'first_payment' => $firstPayment,
            'second_payment' => $secondPayment,
            'second_due_date' => $secondDueDate,
        ];
    }

    public function getClientGracePeriod(int $clientId): int
    {
        $companyId = auth()->user()?->company_id;

        $query = DB::table('client_grace_periods')
            ->where('client_id', $clientId)
            ->where(function($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', today());
            });

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $gracePeriod = $query->orderByDesc('id')->first();

        return $gracePeriod?->grace_days ?? 5;
    }

    public function setClientGracePeriod(int $clientId, int $days, string $reason, int $approvedBy): void
    {
        DB::table('client_grace_periods')->insert([
            'client_id' => $clientId,
            'company_id' => auth()->user()?->company_id,
            'grace_days' => $days,
            'reason' => $reason,
            'approved_by' => $approvedBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function applyPromoCode(int $billId, string $code): array
    {
        $companyId = auth()->user()?->company_id;

        $promoQuery = DB::table('promo_codes')
            ->where('code', $code)
            ->where('is_active', true)
            ->where('valid_from', '<=', today())
            ->where('valid_until', '>=', today());

        if ($companyId) {
            $promoQuery->where('company_id', $companyId);
        }

        $promo = $promoQuery->first();

        if (!$promo) {
            return ['error' => 'Invalid or expired promo code'];
        }

        if ($promo->usage_limit && $promo->used_count >= $promo->usage_limit) {
            return ['error' => 'Promo code usage limit reached'];
        }

        $bill = Bill::find($billId);
        if (!$bill) {
            return ['error' => 'Bill not found'];
        }

        $discount = $promo->discount_type === 'percent'
            ? ($bill->total_amount * $promo->discount_value / 100)
            : $promo->discount_value;

        if ($promo->max_discount) {
            $discount = min($discount, $promo->max_discount);
        }

        DB::table('client_promo_usage')->insert([
            'client_id' => $bill->client_id,
            'company_id' => $bill->company_id,
            'promo_code_id' => $promo->id,
            'bill_id' => $billId,
            'discount_applied' => $discount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('promo_codes')
            ->where('id', $promo->id)
            ->increment('used_count');

        $bill->update([
            'discount' => ($bill->discount ?? 0) + $discount,
            'total_amount' => $bill->total_amount - $discount,
        ]);

        return [
            'success' => true,
            'discount' => $discount,
            'new_total' => $bill->total_amount,
        ];
    }

    public function handleOverpayment(int $clientId, float $overpaymentAmount): void
    {
        $client = Client::findOrFail($clientId);
        $client->increment('wallet_balance', $overpaymentAmount);

        $companyId = $client->company_id;

        DB::table('client_timeline')->insert([
            'client_id' => $clientId,
            'company_id' => $companyId,
            'event_type' => 'payment',
            'title' => 'Overpayment Credit',
            'description' => "৳{$overpaymentAmount} credited to wallet from overpayment",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createRefund(int $clientId, float $amount, string $reason): array
    {
        $refundId = DB::table('refunds')->insertGetId([
            'client_id' => $clientId,
            'company_id' => auth()->user()?->company_id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'refund_id' => $refundId,
            'amount' => $amount,
            'status' => 'pending',
        ];
    }

    public function getCollectionTargets(int $collectorId): array
    {
        $currentMonth = now()->format('Y-m');
        $companyId = auth()->user()?->company_id;

        $targetQuery = DB::table('collection_targets')
            ->where('collector_id', $collectorId)
            ->where('month', $currentMonth);

        if ($companyId) {
            $targetQuery->where('company_id', $companyId);
        }

        $target = $targetQuery->first();

        $achieved = Payment::where('collected_by', $collectorId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return [
            'month' => $currentMonth,
            'target' => $target?->target_amount ?? 0,
            'achieved' => $achieved,
            'remaining' => max(0, ($target?->target_amount ?? 0) - $achieved),
            'percent' => $target?->target_amount > 0 
                ? round(($achieved / $target->target_amount) * 100, 1) 
                : 0,
        ];
    }

    public function getAreaCollectionHeatmap(): array
    {
        $companyId = auth()->user()?->company_id;
        if (!$companyId) {
            return [];
        }

        $zones = DB::table('zones')->where('company_id', $companyId)->get();
        $heatmap = [];

        foreach ($zones as $zone) {
            $totalBilled = DB::table('bills')
                ->join('clients', 'bills.client_id', '=', 'clients.id')
                ->where('clients.zone_id', $zone->id)
                ->where('bills.company_id', $companyId)
                ->whereMonth('bills.created_at', now()->month)
                ->whereYear('bills.created_at', now()->year)
                ->sum('bills.total_amount');

            $collected = DB::table('payments')
                ->join('clients', 'payments.client_id', '=', 'clients.id')
                ->where('clients.zone_id', $zone->id)
                ->where('payments.company_id', $companyId)
                ->whereMonth('payments.created_at', now()->month)
                ->whereYear('payments.created_at', now()->year)
                ->sum('payments.amount');

            $collectionRate = $totalBilled > 0 ? ($collected / $totalBilled) * 100 : 0;

            $heatmap[] = [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'total_billed' => $totalBilled,
                'collected' => $collected,
                'collection_rate' => round($collectionRate, 1),
                'heat_level' => $this->getHeatLevel($collectionRate),
            ];
        }

        usort($heatmap, fn($a, $b) => $b['collection_rate'] <=> $a['collection_rate']);

        return $heatmap;
    }

    protected function getHeatLevel(float $rate): string
    {
        if ($rate >= 90) return 'hot';
        if ($rate >= 70) return 'warm';
        if ($rate >= 50) return 'cool';
        return 'cold';
    }

    public function getAdvancePaymentRewards(int $clientId): array
    {
        $client = Client::find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $advancePayments = Payment::where('client_id', $clientId)
            ->whereRaw('payment_date < (SELECT due_date FROM bills WHERE bills.id = payments.bill_id)')
            ->count();

        $totalPoints = $advancePayments * 10;

        $rewards = DB::table('advance_payment_rewards')
            ->where('client_id', $clientId)
            ->get();

        $redeemedPoints = $rewards->where('is_redeemed', true)->sum('points');
        $availablePoints = $totalPoints - $redeemedPoints;

        return [
            'client_id' => $clientId,
            'total_points' => $totalPoints,
            'available_points' => max(0, $availablePoints),
            'redeemed_points' => $redeemedPoints,
            'advance_payments_count' => $advancePayments,
        ];
    }

    public function getLowRevenueHighUsageClients(): array
    {
        $clients = Client::where('status', 'active')
            ->with('package')
            ->get();

        $alerts = [];

        foreach ($clients as $client) {
            $monthlyFee = $client->monthly_fee ?? $client->package?->price ?? 0;
            
            $usage = DB::table('mikrotik_bandwidth_usage')
                ->where('client_id', $client->id)
                ->whereMonth('usage_date', now()->month)
                ->sum('download_bytes');

            $usageGb = $usage / 1073741824;

            $packageSpeed = $client->package?->speed ?? 10;
            $expectedUsageGb = $packageSpeed * 30 * 3600 * 0.1 / 8;

            if ($usageGb > $expectedUsageGb * 1.5 && $monthlyFee < 1000) {
                $alerts[] = [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'monthly_fee' => $monthlyFee,
                    'usage_gb' => round($usageGb, 2),
                    'expected_usage_gb' => round($expectedUsageGb, 2),
                    'usage_percent' => round(($usageGb / $expectedUsageGb) * 100, 1),
                ];
            }
        }

        usort($alerts, fn($a, $b) => $b['usage_percent'] <=> $a['usage_percent']);

        return $alerts;
    }

    public function detectPackageMismatch(): array
    {
        $mismatches = [];

        $clients = Client::where('status', 'active')
            ->with('package')
            ->get();

        foreach ($clients as $client) {
            if (!$client->package) continue;

            $avgSpeed = DB::table('speed_test_logs')
                ->where('client_id', $client->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->avg('download_speed');

            if ($avgSpeed === null) continue;

            $packageSpeed = $client->package->speed;
            $speedPercent = ($avgSpeed / $packageSpeed) * 100;

            if ($speedPercent < 60 || $speedPercent > 120) {
                $mismatches[] = [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'package' => $client->package->name,
                    'package_speed' => $packageSpeed,
                    'avg_actual_speed' => round($avgSpeed, 2),
                    'speed_percent' => round($speedPercent, 1),
                    'issue' => $speedPercent < 60 ? 'under_delivering' : 'over_delivering',
                ];
            }
        }

        return $mismatches;
    }

    public function getDiscountMisuseAlerts(): array
    {
        $alerts = [];
        $companyId = auth()->user()?->company_id;

        $highDiscountClients = Client::where('permanent_discount', '>', 0)
            ->where('status', 'active')
            ->with('package')
            ->get();

        foreach ($highDiscountClients as $client) {
            $monthlyFee = $client->package?->price ?? 0;
            $discountPercent = $monthlyFee > 0 ? ($client->permanent_discount / $monthlyFee) * 100 : 0;

            if ($discountPercent > 20) {
                $latePaymentsQuery = DB::table('bills')
                    ->join('payments', 'bills.id', '=', 'payments.bill_id')
                    ->where('bills.client_id', $client->id)
                    ->whereRaw('payments.payment_date > bills.due_date');

                if ($companyId) {
                    $latePaymentsQuery->where('bills.company_id', $companyId);
                }

                $latePayments = $latePaymentsQuery->count();

                $totalBills = Bill::where('client_id', $client->id)->count();
                $latePercent = $totalBills > 0 ? ($latePayments / $totalBills) * 100 : 0;

                if ($latePercent > 30) {
                    $alerts[] = [
                        'client_id' => $client->id,
                        'client_name' => $client->name,
                        'discount' => $client->permanent_discount,
                        'discount_percent' => round($discountPercent, 1),
                        'late_payment_percent' => round($latePercent, 1),
                        'reason' => 'High discount with frequent late payments',
                    ];
                }
            }
        }

        return $alerts;
    }
}
