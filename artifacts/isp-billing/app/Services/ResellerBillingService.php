<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\ResellerBill;
use App\Models\ResellerBillPayment;
use App\Models\ResellerClientRecharge;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Services\SimpleMikroTikService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ResellerBillingService
{
    public function generateMonthlyBill(Reseller $reseller, int $month, int $year): ResellerBill
    {
        if ($reseller->type === 'bandwidth') {
            return $this->generateBandwidthMonthlyBill($reseller, $month, $year);
        }

        return $this->generateMacMonthlyBill($reseller, $month, $year);
    }

    protected function generateMacMonthlyBill(Reseller $reseller, int $month, int $year): ResellerBill
    {
        $totalClients = $reseller->clients()->count();
        $activeClients = $reseller->clients()->where('status', 'active')->count();

        $totalRecharge = (float) ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->where('bill_month', $month)
            ->where('bill_year', $year)
            ->selectRaw('COALESCE(SUM(COALESCE(credited_amount, recharge_amount)), 0) as total')
            ->value('total');
        $totalRecharge = max(0, $totalRecharge);

        $previousBill = ResellerBill::where('reseller_id', $reseller->id)
            ->where(function ($q) use ($month, $year) {
                $q->where('bill_year', '<', $year)
                    ->orWhere(function ($q2) use ($month, $year) {
                        $q2->where('bill_year', $year)->where('bill_month', '<', $month);
                    });
            })
            ->orderBy('bill_year', 'desc')
            ->orderBy('bill_month', 'desc')
            ->first();

        $openingBalance = $previousBill ? $previousBill->closing_balance : 0;

        $bill = ResellerBill::updateOrCreate(
            [
                'reseller_id' => $reseller->id,
                'bill_month' => $month,
                'bill_year' => $year,
            ],
            [
                'total_clients' => $totalClients,
                'active_clients' => $activeClients,
                'total_recharge' => $totalRecharge,
                'commission_rate' => 0,
                'opening_balance' => $openingBalance,
                'company_id' => $reseller->company_id,
            ]
        );

        $totalPaid = ResellerBillPayment::where('reseller_id', $reseller->id)
            ->where('reseller_bill_id', $bill->id)
            ->sum('amount');
        $totalDiscount = ResellerBillPayment::where('reseller_id', $reseller->id)
            ->where('reseller_bill_id', $bill->id)
            ->sum('discount_amount');
        $bill->total_paid = $totalPaid;
        $bill->discount_amount = $totalDiscount;
        $bill->commission_amount = 0;
        $bill->due_amount = $totalRecharge - $totalPaid - $totalDiscount;
        $bill->closing_balance = $openingBalance + $totalRecharge - $totalPaid - $totalDiscount;
        $bill->updateStatus();

        return $bill;
    }

    protected function generateBandwidthMonthlyBill(Reseller $reseller, int $month, int $year): ResellerBill
    {
        // Calculate total from per-category (Mbps × rate) pricing
        $categories    = $reseller->getBandwidthCategories();
        $monthlyRate   = $reseller->calculateBandwidthTotal();

        // Fall back to legacy flat rate if no categories configured
        if ($monthlyRate <= 0) {
            $monthlyRate = (float) ($reseller->bandwidth_monthly_rate ?? 0);
        }

        $previousBill = ResellerBill::where('reseller_id', $reseller->id)
            ->where(function ($q) use ($month, $year) {
                $q->where('bill_year', '<', $year)
                    ->orWhere(function ($q2) use ($month, $year) {
                        $q2->where('bill_year', $year)->where('bill_month', '<', $month);
                    });
            })
            ->orderBy('bill_year', 'desc')
            ->orderBy('bill_month', 'desc')
            ->first();

        $openingBalance = $previousBill ? $previousBill->closing_balance : 0;

        $bill = ResellerBill::updateOrCreate(
            [
                'reseller_id' => $reseller->id,
                'bill_month'  => $month,
                'bill_year'   => $year,
            ],
            [
                'total_clients'       => 0,
                'active_clients'      => 0,
                'total_recharge'      => $monthlyRate,
                'commission_rate'     => 0,
                'opening_balance'     => $openingBalance,
                'company_id'          => $reseller->company_id,
                'bandwidth_breakdown' => $categories,
            ]
        );

        // Always refresh from current rates
        $bill->total_recharge      = $monthlyRate;
        $bill->bandwidth_breakdown = $categories;

        $totalPaid = ResellerBillPayment::where('reseller_id', $reseller->id)
            ->where('reseller_bill_id', $bill->id)
            ->sum('amount');
        $totalDiscount = ResellerBillPayment::where('reseller_id', $reseller->id)
            ->where('reseller_bill_id', $bill->id)
            ->sum('discount_amount');

        $bill->total_paid       = $totalPaid;
        $bill->discount_amount  = $totalDiscount;
        $bill->commission_amount = 0;
        $bill->due_amount       = $monthlyRate - $totalPaid - $totalDiscount;
        $bill->closing_balance  = $openingBalance + $monthlyRate - $totalPaid - $totalDiscount;
        $bill->updateStatus();
        $this->recordBwBillCommission($reseller, $bill);

        return $bill;
    }

    /**
     * Generate bandwidth bill with pro-rated adjustment segments.
     * If adjustments exist in the billing month, calculate day-weighted amounts.
     */
    public function generateBandwidthBillWithAdjustments(Reseller $reseller, int $month, int $year): ResellerBill
    {
        $daysInMonth  = Carbon::create($year, $month, 1)->daysInMonth;
        $monthStart   = Carbon::create($year, $month, 1);
        $monthEnd     = Carbon::create($year, $month, $daysInMonth);

        // Get adjustments for this month, per category
        $adjustments = \App\Models\BandwidthAdjustment::where('reseller_id', $reseller->id)
            ->whereBetween('effective_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('effective_date')
            ->get()
            ->groupBy('category_key');

        $categoryMap = [];
        foreach (\App\Models\BandwidthCategory::getStandardCategories() as $key => $cat) {
            $categoryMap[$key] = [
                'mbps_field' => $cat['mbps_field'],
                'rate_field' => $cat['rate_field'],
                'is_custom'  => false,
            ];
        }
        // Include custom (non-system) categories via ResellerBwAllocation
        $customAllocations = \App\Models\ResellerBwAllocation::with('category')
            ->where('reseller_id', $reseller->id)
            ->get();
        foreach ($customAllocations as $alloc) {
            if ($alloc->category && ! $alloc->category->is_system && ((float)$alloc->mbps > 0 || (float)$alloc->rate > 0)) {
                $customKey = 'custom_' . $alloc->category_id;
                $categoryMap[$customKey] = [
                    'mbps_field'   => null,
                    'rate_field'   => null,
                    'is_custom'    => true,
                    'custom_mbps'  => (float)$alloc->mbps,
                    'custom_rate'  => (float)$alloc->rate,
                    'custom_label' => $alloc->category->label,
                ];
            }
        }

        $grandTotal      = 0;
        $breakdownResult = [];

        foreach ($categoryMap as $key => $def) {
            if (!empty($def['is_custom'])) {
                $currentMbps = $def['custom_mbps'];
                $rate        = $def['custom_rate'];
            } else {
                $currentMbps = (float)($reseller->{$def['mbps_field']} ?? 0);
                $rate        = (float)($reseller->{$def['rate_field']} ?? 0);
            }

            if ($rate <= 0) continue;

            $catAdjustments = $adjustments->get($key, collect());

            if ($catAdjustments->isEmpty()) {
                // No adjustments — full month at current rate
                $subtotal = $currentMbps * $rate;
                if ($subtotal > 0) {
                    $grandTotal += $subtotal;
                    $breakdownResult[$key] = [
                        'label'    => (!empty($def['is_custom']))
                                        ? ($def['custom_label'] ?? $key)
                                        : (\App\Models\BandwidthAdjustment::$categoryLabels[$key] ?? $key),
                        'mbps'     => $currentMbps,
                        'rate'     => $rate,
                        'subtotal' => round($subtotal, 2),
                        'segments' => [],
                    ];
                }
                continue;
            }

            // Build segments from adjustments
            // Each adjustment records the NEW mbps starting from effective_date
            // So: before first adjustment → old_mbps of first adjustment record
            $segments    = [];
            $catTotal    = 0;
            $segStart    = $monthStart->copy();

            foreach ($catAdjustments as $adj) {
                $adjDate = Carbon::parse($adj->effective_date);
                if ($adjDate->gt($monthEnd)) break;

                $segEnd  = $adjDate->copy()->subDay();
                if ($segStart->lte($segEnd)) {
                    $days     = $segStart->diffInDays($segEnd) + 1;
                    $mbps     = (float)$adj->old_mbps;
                    $amount   = round($mbps * $rate * $days / $daysInMonth, 2);
                    $catTotal += $amount;
                    $segments[] = [
                        'from'   => $segStart->format('d M'),
                        'to'     => $segEnd->format('d M'),
                        'days'   => $days,
                        'mbps'   => $mbps,
                        'rate'   => $rate,
                        'amount' => $amount,
                    ];
                }
                $segStart = $adjDate->copy();
            }

            // Last segment to end of month
            if ($segStart->lte($monthEnd)) {
                $days     = $segStart->diffInDays($monthEnd) + 1;
                $amount   = round($currentMbps * $rate * $days / $daysInMonth, 2);
                $catTotal += $amount;
                $segments[] = [
                    'from'   => $segStart->format('d M'),
                    'to'     => $monthEnd->format('d M'),
                    'days'   => $days,
                    'mbps'   => $currentMbps,
                    'rate'   => $rate,
                    'amount' => $amount,
                ];
            }

            if ($catTotal > 0) {
                $grandTotal += $catTotal;
                $breakdownResult[$key] = [
                    'label'    => (!empty($def['is_custom']))
                                    ? ($def['custom_label'] ?? $key)
                                    : (\App\Models\BandwidthAdjustment::$categoryLabels[$key] ?? $key),
                    'mbps'     => $currentMbps,
                    'rate'     => $rate,
                    'subtotal' => round($catTotal, 2),
                    'segments' => $segments,
                    'prorated' => true,
                ];
            }
        }

        $previousBill = ResellerBill::where('reseller_id', $reseller->id)
            ->where(function($q) use ($month, $year) {
                $q->where('bill_year', '<', $year)
                  ->orWhere(function($q2) use ($month, $year) {
                    $q2->where('bill_year', $year)->where('bill_month', '<', $month);
                  });
            })
            ->orderBy('bill_year', 'desc')->orderBy('bill_month', 'desc')->first();

        $openingBalance = $previousBill ? $previousBill->closing_balance : 0;

        $bill = ResellerBill::updateOrCreate(
            ['reseller_id' => $reseller->id, 'bill_month' => $month, 'bill_year' => $year],
            [
                'total_clients'       => 0,
                'active_clients'      => 0,
                'total_recharge'      => round($grandTotal, 2),
                'commission_rate'     => 0,
                'opening_balance'     => $openingBalance,
                'company_id'          => $reseller->company_id,
                'bandwidth_breakdown' => $breakdownResult,
            ]
        );

        $bill->total_recharge      = round($grandTotal, 2);
        $bill->bandwidth_breakdown = $breakdownResult;

        $totalPaid     = ResellerBillPayment::where('reseller_id', $reseller->id)->where('reseller_bill_id', $bill->id)->sum('amount');
        $totalDiscount = ResellerBillPayment::where('reseller_id', $reseller->id)->where('reseller_bill_id', $bill->id)->sum('discount_amount');

        $bill->total_paid      = $totalPaid;
        $bill->discount_amount = $totalDiscount;
        $bill->due_amount      = round($grandTotal - $totalPaid - $totalDiscount, 2);
        $bill->closing_balance = $openingBalance + $bill->due_amount;
        $bill->updateStatus();
        $this->recordBwBillCommission($reseller, $bill);

        return $bill;
    }

        public function generateAllResellerBills(int $month, int $year): int
    {
        $resellers = Reseller::where('status', 'active')->get();
        $count = 0;
        foreach ($resellers as $reseller) {
            try {
                $this->generateMonthlyBill($reseller, $month, $year);
                $count++;
            } catch (\Exception $e) {
                Log::warning("Bill generation failed for reseller {$reseller->name}: " . $e->getMessage());
            }
        }
        return $count;
    }

    public function rechargeClient(Reseller $reseller, Client $client, ?array $data = null): ResellerClientRecharge
    {
        // Check if reseller-level instant recharge is disabled
        if (isset($reseller->recharge_enabled) && !$reseller->recharge_enabled) {
            throw new \Exception("Instant recharge is currently DISABLED for this reseller. Please contact ISP admin to enable it.");
        }
        // Check if recharge is blocked for this client
        if ($client->recharge_blocked) {
            throw new \Exception("Recharge is blocked for client \"{$client->name}\" by admin. Please contact support.");
        }

        $now = $data['start_date'] ?? Carbon::now()->toDateString();
        $startDate = Carbon::parse($now);

        $rechargeType = $reseller->recharge_day_type ?? 'multi';

        if (isset($data['days'])) {
            $days = $data['days'];
            if ($rechargeType === 'flexible') {
                $flexibleMin = $reseller->flexible_min_days ?? 1;
                if ($days < $flexibleMin) {
                    throw new \Exception("Minimum recharge is {$flexibleMin} day(s) for this reseller.");
                }
            }
        } elseif ($rechargeType === 'single' && $reseller->single_recharge_day) {
            $dayOfMonth = $reseller->single_recharge_day;
            $endOfMonth = $startDate->copy()->endOfMonth();
            $targetDay = $startDate->copy()->day($dayOfMonth > $endOfMonth->day ? $endOfMonth->day : $dayOfMonth);
            if ($targetDay->lte($startDate)) {
                $targetDay->addMonth();
                $newEnd = $targetDay->copy()->endOfMonth();
                $targetDay->day(min($dayOfMonth, $newEnd->day));
            }
            $days = $startDate->diffInDays($targetDay) + 1;
            if ($days < 1) $days = 30;
        } elseif ($rechargeType === 'flexible') {
            $days = 30;
        } else {
            $days = $reseller->minimum_recharge_days ?? 30;
        }

        $currentExpiry = $client->expire_date ? Carbon::parse($client->expire_date) : null;
        $expiryBase = ($currentExpiry && $currentExpiry->gt($startDate)) ? $currentExpiry : $startDate;
        $endDate = $expiryBase->copy()->addDays($days - 1);

        $package = $client->package;
        $resellerPackage = $reseller->packages()->where('package_id', $client->package_id)->where('is_active', true)->first();

        $basePrice = $resellerPackage ? $resellerPackage->reseller_price : ($package ? $package->price : $client->monthly_fee);
        $rechargeAmount = $data['recharge_amount'] ?? round($basePrice * $days / 30, 2);

        $transactionType = $data['transaction_type'] ?? 'recharge';
        $remarks = $data['remarks'] ?? null;
        $wasInactive = in_array($client->status, ['inactive', 'expired', 'suspended']);

        return \DB::transaction(function () use ($reseller, $client, $startDate, $endDate, $days, $package, $rechargeAmount, $transactionType, $remarks, $wasInactive) {
            if ($reseller->billing_type === 'prepaid') {
                $lockedReseller = Reseller::where('id', $reseller->id)->lockForUpdate()->first();
                if ($lockedReseller->wallet_balance < $rechargeAmount) {
                    throw new \Exception("Insufficient wallet balance. Required: ৳{$rechargeAmount}, Available: ৳{$lockedReseller->wallet_balance}");
                }
                $reseller->wallet_balance = $lockedReseller->wallet_balance;
            } elseif ($reseller->billing_type === 'postpaid' && $reseller->credit_limit > 0) {
                $totalRecharges = ResellerClientRecharge::where('reseller_id', $reseller->id)
                    ->where('transaction_type', 'recharge')
                    ->sum('recharge_amount');
                $totalCurtails = ResellerClientRecharge::where('reseller_id', $reseller->id)
                    ->whereIn('transaction_type', ['curtailed_with_benefits', 'recharge_refund', 'client_left_reversal'])
                    ->sum('recharge_amount');
                $totalPayments = \App\Models\ResellerBillPayment::where('reseller_id', $reseller->id)
                    ->sum('amount');
                $currentDue = $totalRecharges - $totalCurtails - $totalPayments;
                $newDue = $currentDue + $rechargeAmount;
                if ($newDue > $reseller->credit_limit) {
                    throw new \Exception("Credit limit exceeded. Limit: ৳" . number_format($reseller->credit_limit, 2) . ", Current due: ৳" . number_format($currentDue, 2) . ", Recharge: ৳" . number_format($rechargeAmount, 2));
                }
            }

            $packagePrice = $package ? $package->price : $client->monthly_fee;
            $dailyRate = $packagePrice > 0 ? round($packagePrice / 30, 4) : 0;
            $creditedAmount = ($transactionType === 'recharge') ? $rechargeAmount : -$rechargeAmount;

            $recharge = ResellerClientRecharge::create([
                'reseller_id' => $reseller->id,
                'client_id' => $client->id,
                'bill_month' => $startDate->month,
                'bill_year' => $startDate->year,
                'package_id' => $client->package_id,
                'package_name' => $package ? $package->name . '-' . number_format($basePrice, 2) . 'TK' : 'N/A',
                'package_price' => $packagePrice,
                'recharge_amount' => $rechargeAmount,
                'daily_rate' => $dailyRate,
                'credited_amount' => $creditedAmount,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $days,
                'status' => 'active',
                'transaction_type' => $transactionType,
                'recharged_by' => auth()->id(),
                'remarks' => $remarks,
                'company_id' => $reseller->company_id,
            ]);

            if ($reseller->billing_type === 'prepaid') {
                $result = $reseller->debitWallet(
                    $rechargeAmount,
                    "Client recharge: {$client->name} ({$client->username})",
                    "recharge:{$recharge->id}",
                    auth()->id()
                );
                if (!$result) {
                    throw new \Exception("Wallet debit failed. Please try again.");
                }
            }

            $client->expire_date = $endDate->copy()->addDay()->toDateString();
            $client->save();

            if ($wasInactive) {
                $enabled = $this->enableClientOnMikroTik($client, $reseller);
                $client->status = 'active';
                $client->connection_status = $enabled ? 'online' : 'offline';
                $client->save();
            }

            $currentProfile = $client->mikrotik_profile_name;
            if ($currentProfile && strtolower($currentProfile) === 'inactive') {
                $restoreProfile = $client->previous_mikrotik_profile
                    ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                if ($restoreProfile) {
                    $client->mikrotik_profile_name = $restoreProfile;
                    $client->previous_mikrotik_profile = null;
                    $client->save();

                    try {
                        $device = $reseller->mikrotikDevice ?? \App\Models\MikrotikDevice::where('is_active', true)->first();
                        if ($device && $client->username) {
                            $mk = new SimpleMikroTikService();
                            if ($mk->connectFromDevice($device)) {
                                $mk->changePPPoEProfile($client->username, $restoreProfile);
                                $mk->disconnect();
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning("Recharge: MikroTik profile restore failed for {$client->username}: " . $e->getMessage());
                    }
                }
            }

            if ($transactionType === 'recharge') {
                try {
                    $billingService = new BillingService();
                    if (!$billingService->hasBillForMonth($client, $startDate->month, $startDate->year)) {
                        $billingService->generateBill($client, $startDate->month, $startDate->year, true);
                    } else {
                        $billingService->recalculateClientBalance($client);
                    }
                } catch (\Exception $e) {
                    Log::warning("Auto bill generation failed for client {$client->name} on recharge: " . $e->getMessage());
                }
            } else {
                try {
                    (new BillingService())->recalculateClientBalance($client);
                } catch (\Exception $e) {
                    Log::warning("Client balance recalc failed for {$client->name}: " . $e->getMessage());
                }
            }

            $this->updateResellerBillOnRecharge($reseller, $startDate->month, $startDate->year);
            $this->recalculateResellerOverallDue($reseller);

            return $recharge;
        });
    }

    public function curtailClient(Reseller $reseller, Client $client, array $data): ResellerClientRecharge
    {
        $startDate = Carbon::parse($data['start_date'] ?? now()->toDateString());
        $endDate = Carbon::parse($data['end_date'] ?? now()->toDateString());
        $days = $startDate->diffInDays($endDate) + 1;

        $package = $client->package;
        $resellerPackageForCurtail = $reseller->packages()->where('package_id', $client->package_id)->where('is_active', true)->first();
        $basePriceForCurtail = $resellerPackageForCurtail ? $resellerPackageForCurtail->reseller_price : ($package ? $package->price : $client->monthly_fee);
        $packagePrice = $package ? $package->price : $client->monthly_fee;
        $dailyRate = $packagePrice > 0 ? round($packagePrice / 30, 4) : 0;
        $creditedAmount = -($dailyRate * $days);

        return \DB::transaction(function () use ($reseller, $client, $startDate, $endDate, $days, $package, $packagePrice, $dailyRate, $creditedAmount, $data, $basePriceForCurtail) {
            $recharge = ResellerClientRecharge::create([
                'reseller_id' => $reseller->id,
                'client_id' => $client->id,
                'bill_month' => $startDate->month,
                'bill_year' => $startDate->year,
                'package_id' => $client->package_id,
                'package_name' => $package ? $package->name . '-' . number_format($basePriceForCurtail, 2) . 'TK' : 'N/A',
                'package_price' => $packagePrice,
                'recharge_amount' => abs($creditedAmount),
                'daily_rate' => $dailyRate,
                'credited_amount' => $creditedAmount,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $days,
                'status' => 'active',
                'transaction_type' => 'curtailed_with_benefits',
                'recharged_by' => auth()->id(),
                'remarks' => $data['remarks'] ?? null,
                'company_id' => $reseller->company_id,
            ]);

            if ($reseller->billing_type === 'prepaid') {
                $reseller->creditWallet(
                    abs($creditedAmount),
                    "Curtailed: {$client->name} ({$client->username}) - {$days} days",
                    "curtail:{$recharge->id}",
                    auth()->id()
                );
            }

            $this->updateResellerBillOnRecharge($reseller, $startDate->month, $startDate->year);
            $this->recalculateResellerOverallDue($reseller);

            return $recharge;
        });
    }


    // ==================== REVERSE CLIENT RECHARGE (Client Left) ====================
    public function reverseClientRecharge(Reseller $reseller, ResellerClientRecharge $original, array $data): ResellerClientRecharge
    {
        $reverseFrom = \Carbon\Carbon::parse($data['reverse_from'] ?? now()->toDateString());
        $endDate     = \Carbon\Carbon::parse($original->end_date);

        // Unused days = reverseFrom to end_date inclusive (0 if already past end)
        $unusedDays = $reverseFrom->lte($endDate) ? ($reverseFrom->diffInDays($endDate) + 1) : 0;

        $dailyRate    = (float) ($original->daily_rate ?? ($original->package_price > 0 ? round($original->package_price / 30, 4) : 0));
        $refundAmount = round($dailyRate * $unusedDays, 2);

        return \DB::transaction(function () use ($reseller, $original, $reverseFrom, $unusedDays, $dailyRate, $refundAmount, $data) {
            $client = $original->client;

            $reversal = ResellerClientRecharge::create([
                'reseller_id'      => $reseller->id,
                'client_id'        => $original->client_id,
                'bill_month'       => $reverseFrom->month,
                'bill_year'        => $reverseFrom->year,
                'package_id'       => $original->package_id,
                'package_name'     => $original->package_name,
                'package_price'    => $original->package_price,
                'recharge_amount'  => $refundAmount,
                'daily_rate'       => $dailyRate,
                'credited_amount'  => -$refundAmount,
                'start_date'       => $reverseFrom->toDateString(),
                'end_date'         => $original->end_date,
                'days'             => $unusedDays,
                'status'           => 'reversed',
                'transaction_type' => 'client_left_reversal',
                'recharged_by'     => auth()->id(),
                'remarks'          => "Client left — reversal for Recharge #{$original->id} ({$unusedDays} unused days)" . (isset($data['remarks']) && $data['remarks'] ? " | {$data['remarks']}" : ''),
                'company_id'       => $reseller->company_id,
            ]);

            // Credit wallet for prepaid resellers
            if ($reseller->billing_type === 'prepaid' && $refundAmount > 0) {
                $reseller->creditWallet(
                    $refundAmount,
                    "Client left reversal: {$client->name} ({$client->username}) — {$unusedDays} days",
                    "client_left:{$reversal->id}",
                    auth()->id()
                );
            }

            // Tag the original recharge
            $original->update([
                'remarks' => ($original->remarks ? $original->remarks . ' ' : '') . "[Reversed #{$reversal->id} — {$unusedDays} days refunded]",
            ]);

            $this->updateResellerBillOnRecharge($reseller, $reverseFrom->month, $reverseFrom->year);
            $this->recalculateResellerOverallDue($reseller);

            return $reversal;
        });
    }

    public function updateResellerBillOnRecharge(Reseller $reseller, int $month, int $year): void
    {
        $totalRecharge = (float) ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->where('bill_month', $month)
            ->where('bill_year', $year)
            ->selectRaw('COALESCE(SUM(COALESCE(credited_amount, recharge_amount)), 0) as total')
            ->value('total');
        $totalRecharge = max(0, $totalRecharge);

        $previousBill = ResellerBill::where('reseller_id', $reseller->id)
            ->where(function ($q) use ($month, $year) {
                $q->where('bill_year', '<', $year)
                    ->orWhere(function ($q2) use ($month, $year) {
                        $q2->where('bill_year', $year)->where('bill_month', '<', $month);
                    });
            })
            ->orderBy('bill_year', 'desc')
            ->orderBy('bill_month', 'desc')
            ->first();

        $openingBalance = $previousBill ? $previousBill->closing_balance : 0;

        $bill = ResellerBill::updateOrCreate(
            [
                'reseller_id' => $reseller->id,
                'bill_month' => $month,
                'bill_year' => $year,
            ],
            [
                'total_clients' => $reseller->clients()->count(),
                'active_clients' => $reseller->clients()->where('status', 'active')->count(),
                'total_recharge' => $totalRecharge,
                'commission_rate' => 0,
                'opening_balance' => $openingBalance,
                'company_id' => $reseller->company_id,
            ]
        );

        $totalPaid = ResellerBillPayment::where('reseller_id', $reseller->id)
            ->where('reseller_bill_id', $bill->id)
            ->sum('amount');
        $totalDiscount = ResellerBillPayment::where('reseller_id', $reseller->id)
            ->where('reseller_bill_id', $bill->id)
            ->sum('discount_amount');
        $bill->total_paid = $totalPaid;
        $bill->discount_amount = $totalDiscount;
        $bill->commission_amount = 0;
        $bill->due_amount = $totalRecharge - $totalPaid - $totalDiscount;
        $bill->closing_balance = $openingBalance + $totalRecharge - $totalPaid - $totalDiscount;
        $bill->updateStatus();
    }

    public function recalculateResellerOverallDue(Reseller $reseller): void
    {
        $bills = ResellerBill::where('reseller_id', $reseller->id)
            ->orderBy('bill_year')
            ->orderBy('bill_month')
            ->get();

        if ($bills->isEmpty()) {
            return;
        }

        $firstBill = $bills->first();
        $previousClosing = (float) ($firstBill->getOriginal('opening_balance') ?? 0);

        foreach ($bills as $index => $bill) {
            // BW resellers have no client recharges — use stored bandwidth breakdown total
            if ($reseller->type === 'bandwidth') {
                $bwBreakdown = $bill->bandwidth_breakdown;
                if (!empty($bwBreakdown) && is_array($bwBreakdown)) {
                    $netRecharge = array_sum(array_column($bwBreakdown, 'subtotal'));
                } else {
                    $netRecharge = $reseller->calculateBandwidthTotal();
                    if ($netRecharge <= 0) {
                        $netRecharge = (float) ($reseller->bandwidth_monthly_rate ?? 0);
                    }
                }
            } else {
                $netRecharge = (float) ResellerClientRecharge::where('reseller_id', $reseller->id)
                    ->where('bill_month', $bill->bill_month)
                    ->where('bill_year', $bill->bill_year)
                    ->selectRaw('COALESCE(SUM(COALESCE(credited_amount, recharge_amount)), 0) as total')
                    ->value('total');
            }

            $totalPaid = (float) ResellerBillPayment::where('reseller_id', $reseller->id)
                ->where('reseller_bill_id', $bill->id)
                ->sum('amount');
            $totalDiscount = (float) ResellerBillPayment::where('reseller_id', $reseller->id)
                ->where('reseller_bill_id', $bill->id)
                ->sum('discount_amount');

            if ($index > 0) {
                $bill->opening_balance = $previousClosing;
            }
            $bill->total_recharge = max(0, $netRecharge);
            $bill->total_paid = $totalPaid;
            $bill->discount_amount = $totalDiscount;
            $bill->due_amount = $netRecharge - $totalPaid - $totalDiscount;
            $bill->closing_balance = $bill->opening_balance + $netRecharge - $totalPaid - $totalDiscount;
            $bill->updateStatus();

            $previousClosing = $bill->closing_balance;
        }
    }

    public static function getResellerDueToAdmin(Reseller $reseller): float
    {
        if ($reseller->type === 'bandwidth') {
            $totalBilled   = (float) \App\Models\ResellerBill::where('reseller_id', $reseller->id)->sum('total_recharge');
            $totalPayments = (float) \App\Models\ResellerBillPayment::where('reseller_id', $reseller->id)->sum('amount');
            $totalDisc     = (float) \App\Models\ResellerBillPayment::where('reseller_id', $reseller->id)->sum('discount_amount');
            return max(0, $totalBilled - $totalPayments - $totalDisc);
        }
        $totalRecharges = (float) ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->where('transaction_type', 'recharge')
            ->sum('recharge_amount');
        $totalCurtails = (float) ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->whereIn('transaction_type', ['curtailed_with_benefits', 'recharge_refund', 'client_left_reversal'])
            ->sum('recharge_amount');
        $totalReversals = (float) ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->where('transaction_type', 'recharge_reversal')
            ->sum('recharge_amount');
        $totalPayments = (float) \App\Models\ResellerBillPayment::where('reseller_id', $reseller->id)
            ->sum('amount');
        $totalDiscounts = (float) \App\Models\ResellerBillPayment::where('reseller_id', $reseller->id)
            ->sum('discount_amount');

        return max(0, $totalRecharges - $totalCurtails - $totalReversals - $totalPayments - $totalDiscounts);
    }

    public function recordPayment(Reseller $reseller, array $data): ResellerBillPayment
    {
        $amount = (float) ($data['amount'] ?? 0);
        $commissionAmount = 0;
        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        $paymentDate = $data['payment_date'] ?? Carbon::now()->toDateString();

        $month = Carbon::parse($paymentDate)->month;
        $year = Carbon::parse($paymentDate)->year;

        return \DB::transaction(function () use ($reseller, $amount, $commissionAmount, $discountAmount, $paymentDate, $month, $year, $data) {
            $bill = ResellerBill::firstOrCreate(
                ['reseller_id' => $reseller->id, 'bill_month' => $month, 'bill_year' => $year],
                ['total_recharge' => 0, 'total_paid' => 0, 'due_amount' => 0, 'status' => 'unpaid', 'company_id' => $reseller->company_id]
            );

            $payment = ResellerBillPayment::create([
                'reseller_id' => $reseller->id,
                'reseller_bill_id' => $bill->id,
                'amount' => $amount,
                'commission_amount' => $commissionAmount,
                'discount_amount' => $discountAmount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_date' => $paymentDate,
                'notes' => $data['notes'] ?? null,
                'received_by' => auth()->id(),
                'company_id' => $reseller->company_id,
            ]);

            if ($reseller->billing_type === 'postpaid') {
                $reseller->creditWallet(
                    $amount,
                    "Payment received: ৳{$amount}" . ($discountAmount > 0 ? " (Discount: ৳{$discountAmount})" : ''),
                    "payment:{$payment->id}",
                    auth()->id()
                );
            }

            if ($reseller->agent_id && $amount > 0) {
                $agent = \App\Models\Agent::find($reseller->agent_id);
                if ($agent && $agent->status === 'active') {
                    if (!empty($data['referral_commission_percent']) && is_numeric($data['referral_commission_percent'])) {
                        $agentCommissionPercent = (float) $data['referral_commission_percent'];
                    } else {
                        $agentCommissionPercent = $reseller->referral_commission_percent ?? $agent->default_commission ?? 0;
                    }
                    if (!empty($data['referral_commission_amount']) && (float) $data['referral_commission_amount'] > 0) {
                        $agentCommissionAmount = (float) $data['referral_commission_amount'];
                    } else {
                        $agentCommissionAmount = round($amount * $agentCommissionPercent / 100, 2);
                    }

                    if ($agentCommissionAmount > 0) {
                        $existingCommission = \App\Models\AgentCommission::where('agent_id', $agent->id)
                            ->where('type', 'commission')
                            ->where('purpose', 'Reseller Bill Payment')
                            ->where('payment_id', $payment->id)
                            ->first();

                        if (!$existingCommission) {
                            \App\Models\AgentCommission::create([
                                'agent_id' => $agent->id,
                                'type' => 'commission',
                                'payment_id' => $payment->id,
                                'client_name' => 'Reseller: ' . $reseller->name,
                                'client_pppoe_id' => $reseller->reseller_id,
                                'zone_address' => $reseller->area_name_en,
                                'purpose' => 'Reseller Bill Payment',
                                'note' => "Reseller bill payment ৳{$amount} for " . Carbon::create($year, $month, 1)->format('M-Y'),
                                'date' => $paymentDate,
                                'payment_amount' => $amount,
                                'commission_percent' => $agentCommissionPercent,
                                'commission_amount' => $agentCommissionAmount,
                                'month' => $month,
                                'year' => $year,
                                'company_id' => $reseller->company_id,
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            $this->generateMonthlyBill($reseller, $month, $year);
            $this->recalculateResellerOverallDue($reseller);

            return $payment;
        });
    }

    public function getResellerLedger(Reseller $reseller, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = ResellerBill::where('reseller_id', $reseller->id)
            ->orderBy('bill_year', 'asc')
            ->orderBy('bill_month', 'asc');

        $bills = $query->get();

        $ledger = [];
        foreach ($bills as $bill) {
            $monthName = Carbon::create($bill->bill_year, $bill->bill_month, 1)->format('M-Y');
            $ledger[] = [
                'bill_id' => $bill->id,
                'month' => $monthName,
                'bill_month' => $bill->bill_month,
                'bill_year' => $bill->bill_year,
                'total_clients' => $bill->total_clients,
                'active_clients' => $bill->active_clients,
                'opening_balance' => (float) $bill->opening_balance,
                'total_recharge' => (float) $bill->total_recharge,
                'total_paid' => (float) $bill->total_paid,
                'commission_amount' => (float) $bill->commission_amount,
                'discount_amount' => (float) $bill->discount_amount,
                'closing_balance' => (float) $bill->closing_balance,
                'status' => $bill->status,
            ];
        }

        return $ledger;
    }

    public function getRechargeHistory(Reseller $reseller, ?string $date = null, ?int $month = null, ?int $year = null)
    {
        $query = ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->with(['client', 'package', 'rechargedByUser']);

        if ($date) {
            $query->whereDate('created_at', $date);
        }
        if ($month && $year) {
            $query->where('bill_month', $month)->where('bill_year', $year);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function syncResellerClientBilling(Reseller $reseller, ?int $month = null, ?int $year = null): int
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;

        if (!$reseller->auto_recharge) {
            $this->generateMonthlyBill($reseller, $month, $year);
            return 0;
        }

        $clients = $reseller->clients()->where('status', 'active')->with('package')->get();
        $recharged = 0;

        foreach ($clients as $client) {
            $existing = ResellerClientRecharge::where('reseller_id', $reseller->id)
                ->where('client_id', $client->id)
                ->where('bill_month', $month)
                ->where('bill_year', $year)
                ->first();

            if (!$existing) {
                try {
                    $startDay = 1;
                    if (($reseller->recharge_day_type ?? 'multi') === 'single' && $reseller->single_recharge_day) {
                        $startDay = min($reseller->single_recharge_day, Carbon::create($year, $month, 1)->daysInMonth);
                    } else {
                        $startDay = min($client->billing_date ?? 1, 28);
                    }
                    $this->rechargeClient($reseller, $client, [
                        'start_date' => Carbon::create($year, $month, $startDay)->toDateString(),
                    ]);
                    $recharged++;
                } catch (\Exception $e) {
                    \Log::warning("Auto-recharge failed for client {$client->username} under reseller {$reseller->name}: " . $e->getMessage());
                }
            }
        }

        $this->generateMonthlyBill($reseller, $month, $year);
        return $recharged;
    }

    protected function enableClientOnMikroTik(Client $client, Reseller $reseller): bool
    {
        try {
            $device = $reseller->mikrotikDevice;
            if (!$device) {
                Log::warning("No MikroTik device assigned to reseller {$reseller->name} — cannot enable client {$client->username}");
                return false;
            }

            $mikrotik = new SimpleMikroTikService();
            if ($mikrotik->connectFromDevice($device)) {
                $result = $mikrotik->enableClientByType($client);
                if ($result['success']) {
                    Log::info("MikroTik: Enabled client {$client->username} on {$device->name}");
                    return true;
                }
                Log::warning("MikroTik: Failed to enable client {$client->username}: {$result['message']}");
                return false;
            }
            Log::warning("MikroTik: Cannot connect to device {$device->name} for enabling client {$client->username}");
            return false;
        } catch (\Exception $e) {
            Log::error("MikroTik enable error for client {$client->username}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record agent commission for BW reseller monthly bill generation.
     * Commission is based on total_recharge (total monthly bill amount).
     */
    protected function recordBwBillCommission(Reseller $reseller, ResellerBill $bill): void
    {
        if (!$reseller->agent_id || !($bill->total_recharge > 0)) {
            return;
        }
        $agent = \App\Models\Agent::find($reseller->agent_id);
        if (!$agent || $agent->status !== 'active') {
            return;
        }
        $commissionPercent = $reseller->referral_commission_percent ?? $agent->default_commission ?? 0;
        if ($commissionPercent <= 0) {
            return;
        }
        $commissionAmount = round($bill->total_recharge * $commissionPercent / 100, 2);
        if ($commissionAmount <= 0) {
            return;
        }
        $bill->commission_rate   = $commissionPercent;
        $bill->commission_amount = $commissionAmount;
        $bill->save();

        $monthLabel = \Carbon\Carbon::create($bill->bill_year, $bill->bill_month, 1)->format('M-Y');
        $billRef    = '#bill:' . $bill->id;

        $exists = \App\Models\AgentCommission::where('agent_id', $agent->id)
            ->where('type', 'commission')
            ->where('purpose', 'BW Monthly Bill')
            ->where('month', $bill->bill_month)
            ->where('year',  $bill->bill_year)
            ->where('company_id', $reseller->company_id)
            ->where('note', 'like', '%' . $billRef . '%')
            ->exists();

        if (!$exists) {
            \App\Models\AgentCommission::create([
                'agent_id'           => $agent->id,
                'type'               => 'commission',
                'client_name'        => 'BW Reseller: ' . $reseller->name,
                'client_pppoe_id'    => $reseller->reseller_id,
                'zone_address'       => $reseller->area_name_en,
                'purpose'            => 'BW Monthly Bill',
                'note'               => "BW bill \u09f3{$bill->total_recharge} for {$monthLabel} {$billRef}",
                'date'               => \Carbon\Carbon::create($bill->bill_year, $bill->bill_month, 1)->endOfMonth()->toDateString(),
                'payment_amount'     => $bill->total_recharge,
                'commission_percent' => $commissionPercent,
                'commission_amount'  => $commissionAmount,
                'month'              => $bill->bill_month,
                'year'               => $bill->bill_year,
                'company_id'         => $reseller->company_id,
                'created_by'         => auth()->id() ?? null,
            ]);
        }
    }

}
