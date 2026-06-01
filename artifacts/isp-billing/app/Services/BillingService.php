<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\Payment;
use App\Models\AdvancePayment;
use App\Models\CorrectionAuditLog;
use App\Models\MikrotikActionLog;
use App\Models\MikrotikDevice;
use App\Services\ClientActivityService;
use App\Services\MikroTikService;
use App\Services\SmsService;
use App\Events\BillGenerated;
use App\Events\PaymentReceived;
use App\Events\AdvanceReceived;
use App\Events\AdvanceUsed;
use App\Events\ClientStatusChanged;
use App\Events\ClientBalanceChanged;
use App\Events\TemporaryDeadlineApplied;
use App\Events\TemporaryDeadlineExpired;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BillingService
{
    const DEFAULT_DUE_DAYS = 15;
    const AUTO_SUSPEND_DAYS = 30;
    
    public function generateMonthlyBillsForAllClients(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        
        $generated = 0;
        $skipped = 0;
        $errors = [];
        
        $clients = Client::where('status', 'active')
            ->where('monthly_fee', '>', 0)
            ->whereNull('reseller_id')
            ->where(function($q) {
                $q->where('is_free', false)->orWhereNull('is_free');
            })
            ->cursor();
        
        foreach ($clients as $client) {
            try {
                if ($this->hasBillForMonth($client, $month, $year)) {
                    $skipped++;
                    continue;
                }
                
                $this->generateBill($client, $month, $year);
                $generated++;
            } catch (\Exception $e) {
                $errors[] = [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        CorrectionAuditLog::log('Bill', 0, 'monthly_generation', null, [
            'month' => $month,
            'year' => $year,
            'generated' => $generated,
            'skipped' => $skipped,
            'errors' => count($errors)
        ]);
        
        return [
            'generated' => $generated,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }
    
    public function hasBillForMonth(Client $client, int $month, int $year): bool
    {
        return $client->bills()
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('is_cancelled', false)
            ->exists();
    }
    
    /**
     * Generate bill with automatic advance deduction:
     * Bill amount = Monthly Fee (package price)
     * If client has advance balance, automatically use it to pay the bill
     */
    public function generateBill(Client $client, int $month, int $year, bool $suppressSms = false): Bill
    {
        if ($client->is_free) {
            throw new \Exception("Cannot generate bill for free client: {$client->name}");
        }

        if ($this->hasBillForMonth($client, $month, $year)) {
            throw new \Exception("Bill already exists for {$month}/{$year}");
        }
        
        return DB::transaction(function () use ($client, $month, $year, $suppressSms) {
            $isProrated = false;
            
            $packageAmount = (float) $client->monthly_fee;

            if ($client->reseller_id && $client->package_id) {
                $resellerPackage = \App\Models\ResellerPackage::where('reseller_id', $client->reseller_id)
                    ->where('package_id', $client->package_id)
                    ->where('is_active', true)
                    ->first();
                if ($resellerPackage && $resellerPackage->selling_price > 0) {
                    $packageAmount = (float) $resellerPackage->selling_price;
                    if ((float) $client->monthly_fee !== $packageAmount) {
                        $client->update(['monthly_fee' => $packageAmount]);
                    }
                }
            }

            $discount = (float) $client->permanent_discount;
            
            if ($client->connection_date) {
                $connectionMonth = $client->connection_date->month;
                $connectionYear = $client->connection_date->year;
                
                if ($year < $connectionYear || ($year == $connectionYear && $month < $connectionMonth)) {
                    throw new \Exception("Cannot generate bill before client's connection date");
                }
            }

            // billing_starts_at: admin-set date from which billing should begin
            if ($client->billing_starts_at) {
                $startYear  = $client->billing_starts_at->year;
                $startMonth = $client->billing_starts_at->month;
                if ($year < $startYear || ($year === $startYear && $month < $startMonth)) {
                    throw new \Exception("Billing for this client starts from {$client->billing_starts_at->format('F Y')}. Cannot generate bill for {$month}/{$year}.");
                }
            }

            // Total = Package Amount - Discount (always positive)
            $totalAmount = round(max(0, $packageAmount - $discount), 2);
            
            // Check available advance balance BEFORE creating bill.
            // Use max of:
            //   - client.balance  → dynamically calculated (total_payments - total_bills), covers payments collected via recordPayment
            //   - advance_payments table → explicitly tracked manual advances via storeAdvanceCollection
            $client->refresh();
            $dynamicBalance   = (float) $client->balance;
            $explicitAdvance  = $this->getClientAdvanceBalance($client);
            $advanceBalance   = max($dynamicBalance, $explicitAdvance);
            $advanceToUse     = min($advanceBalance, $totalAmount);
            
            $bill = Bill::create([
                'bill_no' => $this->generateBillNumber(),
                'client_id' => $client->id,
                'company_id' => $client->company_id,
                'billing_month' => $month,
                'billing_year' => $year,
                'bill_date' => now(),
                'due_date' => now()->addDays(self::DEFAULT_DUE_DAYS),
                'package_amount' => $packageAmount,
                'previous_due' => 0,
                'discount' => $discount,
                'advance_used' => $advanceToUse,
                'advance_applied_at' => $advanceToUse > 0 ? now() : null,
                'total_amount' => $totalAmount,
                'paid_amount' => $advanceToUse,
                'due_amount' => $totalAmount - $advanceToUse,
                'status' => $advanceToUse >= $totalAmount ? 'paid' : ($advanceToUse > 0 ? 'partial' : 'unpaid'),
                'generated_by' => auth()->id(),
                'is_prorated' => $isProrated,
                'connection_date' => $client->connection_date
            ]);
            
            // Deduct from advance_payments table if advance was used
            if ($advanceToUse > 0) {
                $this->useAdvancePayment($client, $advanceToUse);
                
                Log::info("Bill {$bill->bill_no}: Applied ৳{$advanceToUse} advance payment, remaining due: ৳{$bill->due_amount}");
            }
            
            // Reapply all payments to all bills (handles any remaining distribution)
            $this->reapplyAllPaymentsToBills($client);
            
            // Refresh bill to get updated values
            $bill->refresh();
            
            // Final balance recalculation
            $this->recalculateClientBalance($client);

            // Extend expire_date if advance covered the full bill (active client, due=0)
            $client->refresh();
            $this->extendExpireDateIfActive($client);

            CorrectionAuditLog::log('Bill', $bill->id, 'bill_generated', null, [
                'bill_no' => $bill->bill_no,
                'client_id' => $client->id,
                'amount' => $bill->total_amount
            ]);
            
            ClientActivityService::logBillGenerated($client, $bill);
            
            event(new BillGenerated($bill, 'system', auth()->id(), $suppressSms));
            
            if (!$suppressSms) {
                try {
                    $smsService = app(SmsService::class);
                    $smsService->sendBillNotification($client, $bill);
                } catch (\Exception $smsError) {
                    Log::warning("Bill SMS failed for client {$client->id}: " . $smsError->getMessage());
                }
            }
            
            return $bill;
        });
    }

    public function processPayment(Bill $bill, float $amount, int $paymentMethodId, ?int $collectedBy = null, string $source = 'admin'): Payment
    {
        return DB::transaction(function () use ($bill, $amount, $paymentMethodId, $collectedBy, $source) {
            $bill = Bill::lockForUpdate()->find($bill->id);
            
            if (!$bill) {
                throw new \Exception('Bill not found.');
            }

            if ($bill->status === 'paid' && $bill->due_amount <= 0) {
                throw new \Exception('This bill is already fully paid.');
            }

            if ($amount > $bill->due_amount && $bill->due_amount > 0) {
                $amount = $bill->due_amount;
            }

            if ($amount <= 0) {
                throw new \Exception('Payment amount must be greater than zero.');
            }

            $status = $source === 'technician' ? 'pending' : 'approved';
            
            $payment = Payment::create([
                'payment_no' => $this->generatePaymentNumber(),
                'client_id' => $bill->client_id,
                'company_id' => $bill->company_id,
                'bill_id' => $bill->id,
                'amount' => $amount,
                'payment_method_id' => $paymentMethodId,
                'payment_date' => now(),
                'collected_by' => $collectedBy ?? auth()->id(),
                'status' => $status,
                'source' => $source
            ]);
            
            if ($status === 'approved') {
                $this->applyPaymentToBill($bill, $amount);
            }
            
            CorrectionAuditLog::log('Payment', $payment->id, 'payment_received', null, [
                'payment_no' => $payment->payment_no,
                'bill_id' => $bill->id,
                'amount' => $amount,
                'source' => $source,
                'status' => $status
            ]);
            
            ClientActivityService::logPaymentReceived($bill->client, $payment);
            
            event(new PaymentReceived($payment, $source, $collectedBy ?? auth()->id()));
            
            return $payment;
        });
    }
    
    public function approvePayment(Payment $payment): Payment
    {
        if ($payment->status === 'approved') {
            throw new \Exception('Payment already approved');
        }
        
        return DB::transaction(function () use ($payment) {
            $beforeState = $payment->toArray();
            
            $payment->status = 'approved';
            $payment->approved_by = auth()->id();
            $payment->approved_at = now();
            $payment->save();
            
            $this->reapplyAllPaymentsToBills($payment->client);
            $this->recalculateClientBalance($payment->client);

            $payment->client->refresh();
            if ($payment->client->due_amount <= 0 && $payment->client->temp_deadline_date) {
                $this->clearTemporaryDeadline($payment->client, 'payment');
            }

            // Extend expire_date for active clients when approval clears dues
            $this->extendExpireDateIfActive($payment->client);

            CorrectionAuditLog::log('Payment', $payment->id, 'payment_approved', $beforeState, $payment->toArray());
            
            event(new PaymentReceived($payment, 'admin', auth()->id()));
            
            return $payment;
        });
    }
    
    protected function applyPaymentToBill(Bill $bill, float $amount): void
    {
        $bill->paid_amount += $amount;
        $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
        
        if ($bill->due_amount <= 0) {
            $bill->status = 'paid';
            $this->clearTemporaryDeadline($bill->client, 'payment');
            $this->checkAutoReconnect($bill);
        } elseif ($bill->paid_amount > 0) {
            $bill->status = 'partial';
        }
        
        $bill->save();
        $this->updateClientBalance($bill->client);
    }

    public function processPartialPayment(Client $client, float $amount, int $paymentMethodId, string $source = 'admin'): array
    {
        return DB::transaction(function () use ($client, $amount, $paymentMethodId, $source) {
            $payments = [];
            $remainingAmount = $amount;
            
            $unpaidBills = $client->bills()
                ->where('is_cancelled', false)
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('bill_date')
                ->get();
            
            foreach ($unpaidBills as $bill) {
                if ($remainingAmount <= 0) break;
                
                $paymentAmount = min($remainingAmount, $bill->due_amount);
                $payments[] = $this->processPayment($bill, $paymentAmount, $paymentMethodId, null, $source);
                $remainingAmount -= $paymentAmount;
            }
            
            if ($remainingAmount > 0) {
                $this->addAdvancePayment($client, $remainingAmount, $paymentMethodId);
            }
            
            return $payments;
        });
    }
    
    public function getClientAdvanceBalance(Client $client): float
    {
        return (float) $client->advancePayments()
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');
    }

    protected function useAdvancePayment(Client $client, float $billAmount): float
    {
        $usedAmount = 0;
        $advances = $client->advancePayments()
            ->lockForUpdate()
            ->where('remaining_amount', '>', 0)
            ->orderBy('created_at')
            ->get();
        
        foreach ($advances as $advance) {
            if ($usedAmount >= $billAmount) break;
            
            $useFromThis = min($advance->remaining_amount, $billAmount - $usedAmount);
            $advance->used_amount += $useFromThis;
            $advance->remaining_amount -= $useFromThis;
            $advance->save();
            
            $usedAmount += $useFromThis;
        }
        
        if ($usedAmount > 0) {
            event(new AdvanceUsed($client, $usedAmount, null, 'system', auth()->id()));
        }
        
        return $usedAmount;
    }

    protected function addAdvancePayment(Client $client, float $amount, int $paymentMethodId): AdvancePayment
    {
        $advance = AdvancePayment::create([
            'client_id' => $client->id,
            'company_id' => $client->company_id,
            'amount' => $amount,
            'payment_method_id' => $paymentMethodId,
            'payment_date' => now(),
            'remaining_amount' => $amount,
            'collected_by' => auth()->id()
        ]);
        
        $client->balance = $this->getClientAdvanceBalance($client);
        $client->save();
        
        CorrectionAuditLog::log('AdvancePayment', $advance->id, 'advance_added', null, [
            'client_id' => $client->id,
            'amount' => $amount
        ]);
        
        event(new AdvanceReceived($advance, 'admin', auth()->id()));
        
        $this->recalculateClientBalance($client);
        
        // Dispatch advance payment SMS to queue so response is not blocked
        $advClientId = $client->id;
        $advAmount   = $amount;
        dispatch(function () use ($advClientId, $advAmount) {
            try {
                $cl = \App\Models\Client::find($advClientId);
                if ($cl) {
                    app(\App\Services\SmsService::class)->sendAdvancePaymentConfirmation($cl, $advAmount);
                }
            } catch (\Exception $ex) {
                \Illuminate\Support\Facades\Log::warning("Async advance payment SMS failed for client {$advClientId}: " . $ex->getMessage());
            }
        })->onQueue('default');
        
        return $advance;
    }

    protected function checkAutoReconnect(Bill $bill): void
    {
        $client = $bill->client;
        
        if ($client->status === 'inactive') {
            $oldStatus = $client->status;
            
            $bill->auto_reconnect = true;
            $bill->save();
            
            $client->status = 'active';
            $client->expire_date = now()->addMonthNoOverflow();
            $client->save();
            
            event(new ClientStatusChanged(
                $client,
                $oldStatus,
                'active',
                'auto',
                'Auto-reconnect after bill payment',
                auth()->id()
            ));
            
            $this->restoreMikroTikAfterPayment($client, 'checkAutoReconnect');
        }
    }
    
    public function restoreMikroTikAfterPayment(Client $client, string $context = 'payment'): void
    {
        try {
            $device = $client->mikrotikDevice ?? MikrotikDevice::where('is_active', true)->first();
            $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
            $restoreProfile = $client->previous_mikrotik_profile ?: $originalProfile;

            if ($restoreProfile) {
                $client->mikrotik_profile_name = $restoreProfile;
                $client->previous_mikrotik_profile = null;
                $client->save();
            }

            if ($device && $client->username) {
                // Skip if device is already known to be down (circuit breaker)
                $devId = $device->id ?? md5($device->host . ($device->port ?? 8728));
                if (DeviceCircuitBreaker::isDown('mikrotik', $devId)) {
                    Log::info("BillingService::{$context}: Skipping MikroTik restore for client {$client->id} — device is circuit-broken (down)");
                    return;
                }
                $mikrotik = new MikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                if ($restoreProfile) {
                    $mikrotik->changePPPoEProfile($client->username, $restoreProfile);
                }
                $mikrotik->enablePPPoEUser($client->username);
                $mikrotik->disconnect();
                Log::info("BillingService::{$context}: MikroTik profile restored to '{$restoreProfile}' and user enabled for client {$client->id} ({$client->username})");
            }
        } catch (\Throwable $e) {
            Log::warning("BillingService::{$context}: MikroTik restore failed for client {$client->id}: " . $e->getMessage());
        }
    }

    public function updateClientBalance(Client $client): void
    {
        $this->recalculateClientBalance($client);
    }

    /**
     * SINGLE SOURCE OF TRUTH for client balance calculation.
     * Simplified Betternet approach:
     *   Due = Total Bills - Total Payments
     *   Balance = Total Payments - Total Bills (if positive, means overpayment/advance)
     * 
     * This function MUST be called after ANY billing/payment action.
     */

    /**
     * Extend expire_date for an active client by 1 month when dues are cleared.
     * From current expire_date if it's in the future, otherwise from today.
     */
    protected function extendExpireDateIfActive(Client $client): void
    {
        if ($client->due_amount > 0 || $client->status !== 'active') {
            return;
        }
        $todayDhaka   = \Carbon\Carbon::today('Asia/Dhaka');
        $currentExpiry = $client->expire_date
            ? \Carbon\Carbon::parse($client->expire_date->toDateString(), 'Asia/Dhaka')
            : null;
        $newExpireDate = ($currentExpiry && $currentExpiry->gt($todayDhaka))
            ? $currentExpiry->copy()->addMonthNoOverflow()->toDateString()
            : \Carbon\Carbon::now('Asia/Dhaka')->addMonthNoOverflow()->toDateString();

        if (!$client->expire_date || $client->expire_date->toDateString() !== $newExpireDate) {
            Client::where('id', $client->id)->update(['expire_date' => $newExpireDate]);
            $client->refresh();
            Log::info("BillingService: Active client {$client->id} expire_date extended to {$newExpireDate} after dues cleared");
        }
    }

    public function recalculateClientBalance(Client $client): array
    {
        $client->refresh();
        
        // Simple calculation: Total Bills - Total Payments
        // withoutGlobalScopes() ensures tenant/company scope does NOT filter cross-context records
        $totalBills = (float) $client->bills()
            ->withoutGlobalScopes()
            ->where('is_cancelled', false)
            ->sum('total_amount');
        
        $totalPayments = (float) $client->payments()
            ->withoutGlobalScopes()
            ->where('status', 'approved')
            ->sum('amount');
        
        $difference = $totalBills - $totalPayments;
        
        // Due = positive difference (client owes money)
        $totalDue = max(0, $difference);
        
        // Balance/Advance = negative difference (client has credit)
        $advanceBalance = max(0, -$difference);
        
        $previousDue = $client->due_amount;
        $previousBalance = $client->balance;
        
        $client->due_amount = round($totalDue, 2);
        $client->balance = round($advanceBalance, 2);
        $client->save();
        
        // Log if there was a change
        if ($previousDue != $client->due_amount || $previousBalance != $client->balance) {
            CorrectionAuditLog::log('Client', $client->id, 'balance_recalculated', [
                'previous_due' => $previousDue,
                'previous_balance' => $previousBalance,
            ], [
                'new_due' => $client->due_amount,
                'new_balance' => $client->balance,
            ]);

            event(new ClientBalanceChanged(
                $client,
                $previousDue,
                (float) $client->due_amount,
                $previousBalance,
                (float) $client->balance,
                'recalculation',
                'system',
                auth()->id()
            ));
        }
        
        return [
            'due_amount' => $client->due_amount,
            'balance' => $client->balance,
            'changed' => ($previousDue != $client->due_amount || $previousBalance != $client->balance),
        ];
    }

    public function updateCurrentBillForPackageChange(Client $client, $newPackage, string $changedBy = 'system'): ?Bill
    {
        $now = Carbon::now();
        $bill = Bill::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('billing_month', $now->month)
            ->where('billing_year', $now->year)
            ->where('is_cancelled', false)
            ->whereIn('status', ['unpaid', 'partial'])
            ->first();

        if (!$bill) {
            return null;
        }

        $oldPackageAmount = (float) $bill->package_amount;
        $newPackageAmount = (float) $newPackage->price;

        if ($oldPackageAmount == $newPackageAmount) {
            return $bill;
        }

        $daysInMonth = $now->daysInMonth;
        $remainingDays = $daysInMonth - $now->day + 1;
        $proRatedDiff = round(($newPackageAmount - $oldPackageAmount) * ($remainingDays / $daysInMonth), 2);
        
        $bill->package_amount = $newPackageAmount;
        $bill->total_amount = max(0, (float) $bill->total_amount + $proRatedDiff);

        $paidAmount = (float) $bill->paid_amount;
        $newTotal = (float) $bill->total_amount;

        if ($paidAmount > $newTotal && $newTotal > 0) {
            $overpayment = round($paidAmount - $newTotal, 2);
            $bill->paid_amount = $newTotal;
            $bill->due_amount = 0;
            $bill->status = 'paid';
            $bill->save();

            $client->balance = ($client->balance ?? 0) + $overpayment;
            $client->save();

            Log::info("Bill #{$bill->id} package change created overpayment of {$overpayment}, added to client advance balance (by {$changedBy})");
        } else {
            $bill->due_amount = max(0, $newTotal - $paidAmount);
            $bill->status = $bill->due_amount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');
            $bill->save();
        }

        $this->recalculateClientBalance($client);

        Log::info("Bill #{$bill->id} updated for package change: package_amount {$oldPackageAmount} -> {$newPackageAmount}, total_amount={$bill->total_amount}, due={$bill->due_amount} (by {$changedBy})");

        return $bill;
    }

    public function applyTemporaryDeadline(Client $client, string $deadlineDate, string $reason, ?int $appliedBy = null): array
    {
        return DB::transaction(function () use ($client, $deadlineDate, $reason, $appliedBy) {
            $currentMonth = now()->format('Y-m');
            $appliedBy = $appliedBy ?? auth()->id();

            $client->update([
                'temp_deadline_date' => $deadlineDate,
                'temp_deadline_month' => $currentMonth,
                'temp_deadline_reason' => $reason,
                'temp_deadline_applied_by' => $appliedBy,
                'temp_deadline_applied_at' => now(),
            ]);

            if ($client->status === 'inactive') {
                $oldStatus = $client->status;

                // Resolve correct MikroTik profile (same priority as enable flow).
                // Update mikrotik_profile_name now so UI toggle reflects intended state
                // even if MikroTik router is temporarily unreachable.
                $resolvedProfile = null;
                if (!empty($client->previous_mikrotik_profile) && strtolower($client->previous_mikrotik_profile) !== 'inactive') {
                    $resolvedProfile = $client->previous_mikrotik_profile;
                } elseif (!empty($client->mikrotik_profile_name) && strtolower($client->mikrotik_profile_name) !== 'inactive') {
                    $resolvedProfile = $client->mikrotik_profile_name;
                } else {
                    $client->loadMissing('package');
                    if ($client->package) {
                        $resolvedProfile = $client->package->mikrotik_profile ?? $client->package->name ?? null;
                    }
                }

                $activeUpdate = [
                    'status' => 'active',
                    'last_active_date' => now(),
                    'last_status_change' => now(),
                    'auto_inactive_note' => null,
                    'previous_mikrotik_profile' => null,
                ];
                if ($resolvedProfile) {
                    $activeUpdate['mikrotik_profile_name'] = $resolvedProfile;
                }

                $client->update($activeUpdate);

                try {
                    $deviceControl = app(DeviceControlService::class);
                    $result = $deviceControl->enableClient($client, false);
                    if (!$result['success']) {
                        Log::warning("MikroTik re-enable failed during temp deadline for client {$client->id}: " . ($result['message'] ?? 'Unknown error'));
                    }
                } catch (\Exception $e) {
                    Log::warning("MikroTik re-enable failed during temp deadline for client {$client->id}: " . $e->getMessage());
                }

                event(new ClientStatusChanged($client, $oldStatus, 'active', 'temp_deadline', "Temporary deadline applied", $appliedBy));
            } elseif ($client->status === 'active' && strtolower($client->mikrotik_profile_name ?? '') === 'inactive') {
                // Client is already 'active' in DB but MikroTik profile is still 'Inactive' (stale state).
                // This happens when auto-expire sets profile to 'Inactive' but status wasn't properly reverted.
                // Re-enable MikroTik to sync actual state.
                try {
                    $deviceControl = app(DeviceControlService::class);
                    $result = $deviceControl->enableClient($client, false);
                    if (!$result['success']) {
                        Log::warning("MikroTik profile fix failed during temp deadline for active client {$client->id}: " . ($result['message'] ?? 'Unknown error'));
                    }
                } catch (\Exception $e) {
                    Log::warning("MikroTik profile fix failed during temp deadline for active client {$client->id}: " . $e->getMessage());
                }
            }

            CorrectionAuditLog::log('Client', $client->id, 'temp_deadline_applied', null, [
                'deadline_date' => $deadlineDate,
                'month' => $currentMonth,
                'reason' => $reason,
                'applied_by' => $appliedBy,
            ]);

            ClientActivityService::log($client, 'temp_deadline_applied', "Temporary deadline set to {$deadlineDate}: {$reason}");

            event(new TemporaryDeadlineApplied($client, $deadlineDate, $reason, $appliedBy, $currentMonth));

            return ['success' => true, 'message' => "Temporary deadline applied until {$deadlineDate}"];
        });
    }

    public function clearTemporaryDeadline(Client $client, string $source = 'manual'): void
    {
        if (!$client->temp_deadline_date) {
            return;
        }

        $expiredDate = $client->temp_deadline_date;
        $expiredMonth = $client->temp_deadline_month;

        $client->update([
            'temp_deadline_date' => null,
            'temp_deadline_month' => null,
            'temp_deadline_reason' => null,
            'temp_deadline_applied_by' => null,
            'temp_deadline_applied_at' => null,
        ]);

        CorrectionAuditLog::log('Client', $client->id, 'temp_deadline_expired', [
            'deadline_date' => $expiredDate,
            'month' => $expiredMonth,
        ], null);

        ClientActivityService::log($client, 'temp_deadline_expired', "Temporary deadline expired ({$expiredMonth})");

        event(new TemporaryDeadlineExpired($client, $expiredDate, $expiredMonth, $source));
    }

    public function hasActiveTemporaryDeadline(Client $client): bool
    {
        if (!$client->temp_deadline_date || !$client->temp_deadline_month) {
            return false;
        }

        $currentMonth = now()->format('Y-m');
        if ($client->temp_deadline_month !== $currentMonth) {
            return false;
        }

        return Carbon::parse($client->temp_deadline_date)->isFuture() || Carbon::parse($client->temp_deadline_date)->isToday();
    }

    /**
     * Reapply ALL payments to ALL bills for a client (FIFO order)
     * Handles: linked payments, overpayments rolling over, and unlinked advance payments
     * 
     * IMPORTANT: Tracks advance_used per bill for proper UI display
     */
    public function reapplyAllPaymentsToBills(Client $client): void
    {
        // Get all bills ordered by date (oldest first), with id as tiebreaker
        $bills = $client->bills()
            ->withoutGlobalScopes()
            ->where('is_cancelled', false)
            ->orderBy('billing_year')
            ->orderBy('billing_month')
            ->orderBy('bill_date')
            ->orderBy('id')
            ->get();
        
        // Get all approved payments (withoutGlobalScopes to bypass TenantAware)
        $allPayments = (float) $client->payments()
            ->withoutGlobalScopes()
            ->where('status', 'approved')
            ->sum('amount');
        
        // Get payments linked to specific bills (sum per bill)
        $linkedPaymentsByBill = $client->payments()
            ->withoutGlobalScopes()
            ->where('status', 'approved')
            ->whereNotNull('bill_id')
            ->selectRaw('bill_id, SUM(amount) as total')
            ->groupBy('bill_id')
            ->pluck('total', 'bill_id');
        
        // Get unlinked payments (bill_id = NULL) ordered by date for FIFO linking
        $unlinkedPayments = $client->payments()
            ->withoutGlobalScopes()
            ->where('status', 'approved')
            ->whereNull('bill_id')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
        
        // Get total advance payments from advance_payments table
        $totalAdvancePayments = (float) $client->advancePayments()->sum('amount');
        
        // Track remaining unlinked payments to distribute
        $unlinkedIndex = 0;
        $unlinkedRemainingInCurrent = $unlinkedPayments->count() > 0 
            ? (float) $unlinkedPayments[0]->amount 
            : 0;
        
        // Available pool from advance_payments table only
        $advancePool = $totalAdvancePayments;
        
        // Apply payments to bills in FIFO order
        foreach ($bills as $bill) {
            $billAmount = (float) $bill->total_amount;
            
            // Get linked payments for this specific bill
            $linkedAmount = (float) ($linkedPaymentsByBill[$bill->id] ?? 0);
            
            // Calculate remaining due after linked payments
            $remainingBillDue = $billAmount - $linkedAmount;
            
            // Link unlinked payments to this bill
            while ($remainingBillDue > 0 && $unlinkedIndex < $unlinkedPayments->count()) {
                $payment = $unlinkedPayments[$unlinkedIndex];
                
                if ($unlinkedRemainingInCurrent <= 0) {
                    $unlinkedIndex++;
                    if ($unlinkedIndex < $unlinkedPayments->count()) {
                        $unlinkedRemainingInCurrent = (float) $unlinkedPayments[$unlinkedIndex]->amount;
                    }
                    continue;
                }
                
                // Link this payment to the bill
                $payment->update(['bill_id' => $bill->id]);
                $linkedAmount += $unlinkedRemainingInCurrent;
                $remainingBillDue -= $unlinkedRemainingInCurrent;
                
                $unlinkedIndex++;
                if ($unlinkedIndex < $unlinkedPayments->count()) {
                    $unlinkedRemainingInCurrent = (float) $unlinkedPayments[$unlinkedIndex]->amount;
                } else {
                    $unlinkedRemainingInCurrent = 0;
                }
            }
            
            // Apply from advance_payments table pool if still due
            $advanceUsedForThisBill = 0;
            if ($remainingBillDue > 0 && $advancePool > 0) {
                $advanceUsedForThisBill = min($remainingBillDue, $advancePool);
                $advancePool -= $advanceUsedForThisBill;
                $linkedAmount += $advanceUsedForThisBill;
                $remainingBillDue -= $advanceUsedForThisBill;
            }
            
            // Update bill
            $bill->paid_amount = $linkedAmount;
            $bill->due_amount = max(0, $billAmount - $bill->paid_amount);
            $bill->advance_used = $advanceUsedForThisBill;
            if ($advanceUsedForThisBill > 0 && !$bill->advance_applied_at) {
                $bill->advance_applied_at = now();
            } elseif ($advanceUsedForThisBill == 0) {
                $bill->advance_applied_at = null;
                $bill->advance_used = 0;
            }
            
            if ($bill->due_amount == 0) {
                $bill->status = 'paid';
            } elseif ($bill->paid_amount > 0) {
                $bill->status = 'partial';
            } else {
                $bill->status = 'unpaid';
            }
            
            $bill->save();
        }
        
        // Update advance_payments remaining amounts
        $advances = $client->advancePayments()->orderBy('created_at')->get();
        $totalBillsAmount = (float) $bills->sum('total_amount');
        $usedFromAdvanceTable = max(0, min($totalAdvancePayments, $totalBillsAmount - $allPayments));
        $toConsume = $usedFromAdvanceTable;
        
        foreach ($advances as $advance) {
            if ($toConsume <= 0) {
                $advance->used_amount = 0;
                $advance->remaining_amount = $advance->amount;
            } else {
                $useFromThis = min($advance->amount, $toConsume);
                $advance->used_amount = $useFromThis;
                $advance->remaining_amount = $advance->amount - $useFromThis;
                $toConsume -= $useFromThis;
            }
            $advance->save();
        }
    }

    /**
     * Apply payment to client's unpaid bills (FIFO - oldest first)
     * Returns remaining amount after applying to all bills (becomes advance)
     */
    public function applyPaymentToClientBills(Client $client, Payment $payment, float $amount): float
    {
        $remainingAmount = $amount;
        $appliedBills = [];
        
        // Get unpaid bills ordered by date (oldest first)
        $unpaidBills = $client->bills()
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('is_cancelled', false)
            ->orderBy('billing_year')
            ->orderBy('billing_month')
            ->orderBy('bill_date')
            ->get();
        
        foreach ($unpaidBills as $bill) {
            if ($remainingAmount <= 0) break;
            
            $applyAmount = min($remainingAmount, $bill->due_amount);
            
            if ($applyAmount > 0) {
                $bill->paid_amount += $applyAmount;
                $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
                
                if ($bill->due_amount <= 0) {
                    $bill->status = 'paid';
                } elseif ($bill->paid_amount > 0) {
                    $bill->status = 'partial';
                }
                
                $bill->save();
                
                $appliedBills[] = [
                    'bill_id' => $bill->id,
                    'bill_no' => $bill->bill_no,
                    'applied_amount' => $applyAmount,
                ];
                
                $remainingAmount -= $applyAmount;
            }
        }
        
        // Link payment to first bill if not already linked
        if (!$payment->bill_id && count($appliedBills) > 0) {
            $payment->bill_id = $appliedBills[0]['bill_id'];
            $payment->save();
        }
        
        // Log the application
        if (count($appliedBills) > 0) {
            CorrectionAuditLog::log('Payment', $payment->id, 'payment_applied_to_bills', null, [
                'applied_bills' => $appliedBills,
                'remaining_as_advance' => $remainingAmount,
            ]);
        }
        
        return $remainingAmount;
    }

    /**
     * Record payment using simplified Betternet approach.
     * Payments are applied to bills for display/tracking purposes.
     * Balance is calculated dynamically as: Total Payments - Total Bills
     * No AdvancePayment records created - overpayment calculated dynamically.
     */
    public function recordPayment(Client $client, array $paymentData, int $collectedBy, string $source = 'admin'): Payment
    {
        return DB::transaction(function () use ($client, $paymentData, $collectedBy, $source) {
            $amount = (float) $paymentData['amount'];
            
            // Create payment record
            $payment = Payment::create([
                'payment_no' => $this->generatePaymentNumber(),
                'client_id' => $client->id,
                'company_id' => $client->company_id,
                'bill_id' => $paymentData['bill_id'] ?? null,
                'amount' => $amount,
                'payment_method_id' => $paymentData['payment_method_id'] ?? null,
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'money_receipt_no' => $paymentData['money_receipt_no'] ?? null,
                'remarks' => $paymentData['remarks'] ?? null,
                'reason' => $paymentData['reason'] ?? null,
                'collected_by' => $collectedBy,
                'status' => 'approved',
                'source' => $source,
                // Auto-submit only when admin/super-admin collected payment themselves.
                // If they recorded it for someone else (different collected_by), that person
                // must still submit it via Collection Transfer.
                'submitted_to_admin' => in_array($source, ['admin', 'super-admin']) && ($collectedBy === null || $collectedBy === auth()->id()),
            ]);
            

            // Track in StaffDue when admin records payment collected by someone else
            if (in_array($source, ['admin', 'super-admin']) && $collectedBy && $collectedBy !== auth()->id()) {
                \App\Models\StaffDue::lockForUpdate()->firstOrCreate(
                    ['user_id' => $collectedBy],
                    ['total_collected' => 0, 'total_submitted' => 0, 'current_due' => 0, 'company_id' => $client->company_id]
                );
                \App\Models\StaffDue::where('user_id', $collectedBy)->increment('total_collected', $amount);
                \App\Models\StaffDue::where('user_id', $collectedBy)->increment('current_due', $amount);
                \App\Models\StaffDue::where('user_id', $collectedBy)->update(['last_collection_date' => now()]);
            }

            // Apply payment to bills (for display/tracking in billing history)
            $this->applyPaymentToClientBills($client, $payment, $amount);

            // Auto-credit agent commission if client has active agent
            $this->creditAgentCommission($client, $payment, $amount);
            
            // CRITICAL: Recalculate client balance (Due = Total Bills - Total Payments)
            $this->recalculateClientBalance($client);
            $client->refresh();
            
            // Clear temp deadline when payment clears dues
            if ($client->due_amount <= 0 && $client->temp_deadline_date) {
                $this->clearTemporaryDeadline($client, 'payment');
            }

            // Auto-reactivate if no dues (skip if already activated by checkAutoReconnect/restoreClient)
            // Skip auto-reactivation if manually deactivated by admin
            $isManuallyDeactivated = $client->auto_inactive_note && !str_contains($client->auto_inactive_note, 'due payment') && !str_contains($client->auto_inactive_note, 'Auto-expired');
            if ($client->due_amount <= 0 && $client->status !== 'active' && !$isManuallyDeactivated) {
                $oldStatus = $client->status;
                $todayDhaka = \Carbon\Carbon::today('Asia/Dhaka');
                $newExpireDate = ($client->expire_date && $client->expire_date->lte($todayDhaka))
                    ? \Carbon\Carbon::now('Asia/Dhaka')->addMonthNoOverflow()->toDateString()
                    : ($client->expire_date ? $client->expire_date->toDateString() : null);

                $client->status = 'active';
                $client->last_active_date = now();
                $client->last_status_change = now();
                $client->auto_inactive_note = null;
                if ($newExpireDate) {
                    $client->expire_date = $newExpireDate;
                }
                $client->save();
                
                ClientActivityService::logStatusChange($client, $oldStatus, 'active', 'Active - Payment cleared');
                
                // Dispatch MikroTik restore asynchronously so HTTP response is not blocked
                $clientId = $client->id;
                dispatch(function () use ($clientId) {
                    try {
                        $freshClient = \App\Models\Client::with(['mikrotikDevice','package'])->find($clientId);
                        if ($freshClient) {
                            app(\App\Services\BillingService::class)->restoreMikroTikAfterPayment($freshClient, 'recordPaymentAsync');
                        }
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\Log::warning("Async MikroTik restore failed for client {$clientId}: " . $ex->getMessage());
                    }
                })->onQueue('default');
                
                event(new ClientStatusChanged(
                    $client,
                    $oldStatus,
                    'active',
                    'auto',
                    'Active - Payment cleared',
                    $collectedBy
                ));
                
                try {
                    $telegram = new TelegramService();
                    $telegram->sendClientConnected($client->client_id ?? $client->id, null, 'Online', $client->name ?? '', $client->username ?? '');
                } catch (\Throwable $e) {
                    Log::warning('Telegram connect notification failed: ' . $e->getMessage());
                }
            }

            // Extend expire_date for ACTIVE clients when payment clears dues (early payment / advance scenario)
            $this->extendExpireDateIfActive($client);

            // Log activity
            ClientActivityService::logPaymentReceived($client, $payment);
            
            CorrectionAuditLog::log('Payment', $payment->id, 'payment_recorded', null, [
                'client_id' => $client->id,
                'amount' => $amount,
                'source' => $source,
            ]);

            try {
                $paymentMethodName = '';
                $paymentRef = '';
                if ($payment->payment_method_id) {
                    $pm = \App\Models\PaymentMethod::find($payment->payment_method_id);
                    $paymentMethodName = $pm->name ?? '';
                }
                $methodLower = strtolower($paymentMethodName);
                if (in_array($methodLower, ['cash', 'hand_cash'])) {
                    $paymentRef = $payment->money_receipt_no ?? '';
                } elseif (in_array($methodLower, ['bank', 'bank_transfer'])) {
                    $paymentRef = $payment->transaction_id ?? $payment->money_receipt_no ?? '';
                } else {
                    $paymentRef = $payment->transaction_id ?? '';
                }
                $collectorName = '';
                if ($collectedBy) {
                    $collector = \App\Models\User::find($collectedBy);
                    if ($collector) $collectorName = $collector->name;
                }
                $billInvoice = '';
                if ($payment->bill_id) {
                    $bill = Bill::find($payment->bill_id);
                    if ($bill) $billInvoice = $bill->invoice_no ?? '';
                }
                $senderNumber = $paymentData['sender_number'] ?? '';
                // Dispatch Telegram notification to background queue to avoid blocking HTTP response
                $tgArgs = [
                    'clientId'      => $client->client_id ?? $client->id,
                    'clientName'    => $client->name,
                    'amount'        => (float) $amount,
                    'method'        => $paymentMethodName ?: 'N/A',
                    'username'      => $client->username ?? '',
                    'collectorName' => $collectorName,
                    'billInvoice'   => $billInvoice,
                    'due'           => (float) ($client->due_amount ?? 0),
                    'payDate'       => $payment->payment_date ? (is_string($payment->payment_date) ? $payment->payment_date : $payment->payment_date->format('d-m-Y')) : '',
                    'payRef'        => $paymentRef,
                    'senderNumber'  => $senderNumber,
                ];
                dispatch(function () use ($tgArgs) {
                    try {
                        $tg = new \App\Services\TelegramService();
                        $tg->sendPaymentNotification(
                            $tgArgs['clientId'], $tgArgs['clientName'], $tgArgs['amount'],
                            $tgArgs['method'], $tgArgs['username'], $tgArgs['collectorName'],
                            $tgArgs['billInvoice'], $tgArgs['due'], $tgArgs['payDate'],
                            $tgArgs['payRef'], $tgArgs['senderNumber']
                        );
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\Log::warning('Async Telegram payment notification failed: ' . $ex->getMessage());
                    }
                })->onQueue('default');
            } catch (\Throwable $e) {
                Log::warning('Telegram payment notification setup failed: ' . $e->getMessage());
            }

            return $payment;
        });
    }

    public function correctBill(Bill $bill, array $corrections, string $reason): Bill
    {
        return DB::transaction(function () use ($bill, $corrections, $reason) {
            $beforeState = $bill->toArray();
            
            if (isset($corrections['package_amount'])) {
                $bill->package_amount = $corrections['package_amount'];
            }
            if (isset($corrections['discount'])) {
                $bill->discount = $corrections['discount'];
            }
            if (isset($corrections['adjustment'])) {
                $bill->adjustment = $corrections['adjustment'];
            }
            
            $bill->total_amount = $bill->package_amount + ($bill->previous_due ?? 0) - ($bill->discount ?? 0) + ($bill->adjustment ?? 0);
            $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
            
            if ($bill->due_amount <= 0) {
                $bill->status = 'paid';
            } elseif ($bill->paid_amount > 0) {
                $bill->status = 'partial';
            } else {
                $bill->status = 'unpaid';
            }
            
            $bill->remarks = ($bill->remarks ? $bill->remarks . "\n" : '') . "[Corrected] " . $reason;
            $bill->save();
            
            $this->updateClientBalance($bill->client);
            
            CorrectionAuditLog::log('Bill', $bill->id, 'bill_corrected', $beforeState, array_merge($bill->toArray(), ['reason' => $reason]));
            
            return $bill;
        });
    }
    
    public function cancelBill(Bill $bill, string $reason): Bill
    {
        return DB::transaction(function () use ($bill, $reason) {
            $beforeState = $bill->toArray();
            
            $bill->status = 'cancelled';
            $bill->is_cancelled = true;
            $bill->due_amount = 0;
            $bill->remarks = ($bill->remarks ? $bill->remarks . "\n" : '') . "[Cancelled] " . $reason;
            $bill->save();
            
            $this->updateClientBalance($bill->client);
            
            CorrectionAuditLog::log('Bill', $bill->id, 'bill_cancelled', $beforeState, array_merge($bill->toArray(), ['reason' => $reason]));
            
            return $bill;
        });
    }
    
    public function cancelPayment(Payment $payment, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            $beforeState = $payment->toArray();
            $client = $payment->client;
            
            if ($payment->bill && $payment->status === 'approved') {
                $bill = $payment->bill;
                $bill->paid_amount = max(0, $bill->paid_amount - $payment->amount);
                $bill->due_amount = max(0, $bill->total_amount - $bill->paid_amount);
                
                if ($bill->due_amount > 0 && $bill->paid_amount > 0) {
                    $bill->status = 'partial';
                } elseif ($bill->due_amount > 0) {
                    $bill->status = 'unpaid';
                }
                $bill->save();
            }
            
            $payment->status = 'cancelled';
            $payment->remarks = ($payment->remarks ? $payment->remarks . "\n" : '') . "[Cancelled] " . $reason;
            $payment->save();
            
            $this->recalculateClientBalance($client);
            
            CorrectionAuditLog::log('Payment', $payment->id, 'payment_cancelled', $beforeState, array_merge($payment->toArray(), ['reason' => $reason]));
            
            return $payment;
        });
    }
    
    public function extendBillDueDate(Bill $bill, int $days, string $reason, bool $sendSms = true): Bill
    {
        $beforeState = $bill->toArray();
        $client = $bill->client;
        
        $bill->due_date = Carbon::parse($bill->due_date)->addDays($days);
        $bill->remarks = ($bill->remarks ? $bill->remarks . "\n" : '') . "[Extended +{$days} days] " . $reason;
        $bill->save();
        
        CorrectionAuditLog::log('Bill', $bill->id, 'due_date_extended', $beforeState, [
            'new_due_date' => $bill->due_date->toDateString(),
            'days_extended' => $days,
            'reason' => $reason
        ]);
        
        if ($client->status === 'inactive') {
            try {
                $device = $client->mikrotikDevice ?? MikrotikDevice::where('is_active', true)->first();
                if ($device && $client->username) {
                    $mikrotik = new MikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                    $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
                    if ($originalProfile) {
                        $mikrotik->changePPPoEProfile($client->username, $originalProfile);
                    } else {
                        $mikrotik->enablePPPoEUser($client->username);
                    }
                    $mikrotik->disconnect();
                    
                    $client->update(['status' => 'active', 'last_active_date' => now(), 'last_status_change' => now(), 'auto_inactive_note' => null]);
                    Log::info("BillingService::extendDueDate: Profile restored for client {$client->id}");
                }
            } catch (\Throwable $e) {
                Log::warning("BillingService::extendDueDate: MikroTik restore failed for client {$client->id}: " . $e->getMessage());
            }
        }
        
        return $bill;
    }

    public function autoDisconnectOverdueClients(): array
    {
        $disconnected = 0;
        $mikrotikFailed = 0;
        $mikrotikService = app(MikroTikService::class);
        
        $clients = Client::where('status', 'active')
            ->where('due_amount', '>', 0)
            ->get();
            
        foreach ($clients as $client) {
            if ($this->getClientAdvanceBalance($client) > 0) {
                continue;
            }
            
            $hasOverdueBill = $client->bills()
                ->where('is_cancelled', false)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->where('due_date', '<', now()->subDays(self::AUTO_SUSPEND_DAYS))
                ->exists();
                
            if ($hasOverdueBill) {
                $result = $mikrotikService->suspendClient(
                    $client,
                    MikrotikActionLog::TRIGGER_AUTO_SUSPEND,
                    null,
                    true
                );
                
                $client->auto_inactive_note = 'Auto-inactive due to overdue bills exceeding ' . self::AUTO_SUSPEND_DAYS . ' days';
                $client->save();
                
                try {
                    $smsService = app(SmsService::class);
                    $smsService->sendDisconnectNotice($client);
                } catch (\Exception $smsError) {
                    Log::warning("Auto-disconnect SMS failed for client {$client->id}: " . $smsError->getMessage());
                }
                
                if ($result['success']) {
                    $disconnected++;
                } else {
                    $mikrotikFailed++;
                }
            }
        }
        
        return [
            'disconnected' => $disconnected,
            'mikrotik_failed' => $mikrotikFailed
        ];
    }
    
    public function autoRestoreClients(): array
    {
        $restored = 0;
        $mikrotikFailed = 0;
        
        $clients = Client::where('status', 'inactive')
            ->where(function ($q) {
                $q->where('due_amount', '<=', 0)
                  ->orWhereHas('advancePayments', function ($q) {
                      $q->where('remaining_amount', '>', 0);
                  });
            })
            ->get();
            
        foreach ($clients as $client) {
            try {
                $device = $client->mikrotikDevice ?? MikrotikDevice::where('is_active', true)->first();
                if ($device && $client->username) {
                    $mikrotik = new MikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                    $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
                    if ($originalProfile) {
                        $mikrotik->changePPPoEProfile($client->username, $originalProfile);
                    } else {
                        $mikrotik->enablePPPoEUser($client->username);
                    }
                    $mikrotik->disconnect();

                    $todayDhaka = \Carbon\Carbon::today('Asia/Dhaka');
                    $newExpireDate = ($client->expire_date && $client->expire_date->lte($todayDhaka))
                        ? \Carbon\Carbon::now('Asia/Dhaka')->addMonthNoOverflow()->toDateString()
                        : ($client->expire_date ? $client->expire_date->toDateString() : null);

                    $client->update([
                        'status' => 'active',
                        'last_active_date' => now(),
                        'last_status_change' => now(),
                        'auto_inactive_note' => null,
                        'expire_date' => $newExpireDate,
                    ]);

                    $restored++;
                    Log::info("BillingService::autoRestore: Profile restored for client {$client->id} ({$client->username})");
                }
            } catch (\Throwable $e) {
                $mikrotikFailed++;
                Log::warning("BillingService::autoRestore: Failed for client {$client->id}: " . $e->getMessage());
            }
        }
        
        return [
            'restored' => $restored,
            'mikrotik_failed' => $mikrotikFailed
        ];
    }
    
    
    public function runDailyIntegrityCheck(): array
    {
        $mismatches = [];
        
        $clients = Client::with(['bills', 'advancePayments'])->get();
        
        foreach ($clients as $client) {
            $calculatedDue = $client->bills()
                ->where('is_cancelled', false)
                ->whereIn('status', ['unpaid', 'partial'])
                ->sum('due_amount');
            
            $calculatedAdvance = $client->advancePayments()
                ->where('remaining_amount', '>', 0)
                ->sum('remaining_amount');
            
            if (abs($client->due_amount - $calculatedDue) > 0.01 || 
                abs($client->balance - $calculatedAdvance) > 0.01) {
                $mismatches[] = [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'stored_due' => $client->due_amount,
                    'calculated_due' => $calculatedDue,
                    'stored_balance' => $client->balance,
                    'calculated_balance' => $calculatedAdvance
                ];
                
                $client->due_amount = $calculatedDue;
                $client->balance = $calculatedAdvance;
                $client->save();
            }
        }
        
        if (count($mismatches) > 0) {
            CorrectionAuditLog::log('System', 0, 'integrity_check', null, [
                'mismatches_found' => count($mismatches),
                'auto_corrected' => true
            ]);
        }
        
        return $mismatches;
    }
    
    public function getClientBillingHistory(Client $client): array
    {
        return $client->bills()
            ->with('generatedBy')
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'bill_no' => $bill->bill_no,
                    'billing_month' => $bill->billing_month,
                    'billing_year' => $bill->billing_year,
                    'bill_date' => $bill->bill_date->format('Y-m-d'),
                    'due_date' => $bill->due_date->format('Y-m-d'),
                    'package_amount' => $bill->package_amount,
                    'discount' => $bill->discount,
                    'total_amount' => $bill->total_amount,
                    'paid_amount' => $bill->paid_amount,
                    'due_amount' => $bill->due_amount,
                    'status' => $bill->status,
                    'generated_by' => $bill->generatedBy?->name ?? 'System',
                    'created_at' => $bill->created_at->format('Y-m-d H:i:s')
                ];
            })
            ->toArray();
    }

    protected function generateBillNumber(): string
    {
        $prefix = 'BIL-' . date('Ym') . '-';

        $maxNum = (int) Bill::withoutGlobalScopes()
            ->where('bill_no', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(bill_no FROM '[0-9]+$') AS INTEGER)) as max_num")
            ->value('max_num');

        $number = $maxNum + 1;
        $billNo = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);

        while (Bill::withoutGlobalScopes()->where('bill_no', $billNo)->exists()) {
            $number++;
            $billNo = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        return $billNo;
    }

    protected function generatePaymentNumber(): string
    {
        $prefix = 'PAY-' . date('Ym') . '-';

        // withoutGlobalScopes() ensures we find the true MAX across ALL companies,
        // preventing duplicate payment_no when different tenants share the same DB.
        $maxNum = (int) Payment::withoutGlobalScopes()
            ->where('payment_no', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(payment_no FROM '[0-9]+$') AS INTEGER)) as max_num")
            ->value('max_num');

        $number = $maxNum + 1;
        $paymentNo = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);

        while (Payment::withoutGlobalScopes()->where('payment_no', $paymentNo)->exists()) {
            $number++;
            $paymentNo = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        return $paymentNo;
    }
    
    public function getBillingDashboardStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        return [
            'total_billed' => Bill::where('billing_month', $currentMonth)
                ->where('billing_year', $currentYear)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
            'total_collected' => Payment::whereMonth('payment_date', $currentMonth)
                ->whereYear('payment_date', $currentYear)
                ->where('status', 'approved')
                ->sum('amount'),
            'total_due' => Bill::where('is_cancelled', false)->whereIn('status', ['unpaid', 'partial'])->sum('due_amount'),
            'overdue_count' => Bill::where('is_cancelled', false)->whereIn('status', ['unpaid', 'partial'])
                ->where('due_date', '<', now())
                ->count(),
            'bills_generated' => Bill::where('billing_month', $currentMonth)
                ->where('billing_year', $currentYear)
                ->count(),
            'paid_bills' => Bill::where('billing_month', $currentMonth)
                ->where('billing_year', $currentYear)
                ->where('status', 'paid')
                ->count(),
            'partial_bills' => Bill::where('billing_month', $currentMonth)
                ->where('billing_year', $currentYear)
                ->where('status', 'partial')
                ->count(),
            'unpaid_bills' => Bill::where('billing_month', $currentMonth)
                ->where('billing_year', $currentYear)
                ->where('status', 'unpaid')
                ->count(),
            'advance_balance_total' => AdvancePayment::where('remaining_amount', '>', 0)->sum('remaining_amount'),
        ];
    }

    public function getClientLedger(Client $client): array
    {
        $ledger = [];
        
        $bills = $client->bills()->orderBy('bill_date')->get();
        $payments = $client->payments()->with('paymentMethod')->where('status', 'approved')->orderBy('payment_date')->get();
        
        $transactions = collect();
        
        foreach ($bills as $bill) {
            $transactions->push([
                'date' => $bill->bill_date,
                'type' => 'bill',
                'reference' => $bill->bill_no,
                'description' => "Bill for {$bill->billing_month}/{$bill->billing_year}",
                'debit' => $bill->total_amount,
                'credit' => 0
            ]);
        }
        
        foreach ($payments as $payment) {
            $senderNum = null;
            if ($payment->remarks && preg_match('/Sender:\s*([\d\-\+]+)/', $payment->remarks, $m)) {
                $senderNum = $m[1];
            }
            $transactions->push([
                'date' => $payment->payment_date,
                'type' => 'payment',
                'reference' => $payment->payment_no,
                'description' => 'Payment received',
                'debit' => 0,
                'credit' => $payment->amount,
                'method' => $payment->paymentMethod->name ?? 'Cash',
                'transaction_id' => $payment->transaction_id,
                'money_receipt_no' => $payment->money_receipt_no,
                'sender_number' => $senderNum,
                'remarks' => $payment->remarks,
            ]);
        }
        
        $sorted = $transactions->sortBy('date');
        $balance = 0;
        
        foreach ($sorted as $item) {
            $balance = $balance + $item['debit'] - $item['credit'];
            $item['balance'] = $balance;
            $ledger[] = $item;
        }
        
        return $ledger;
    }

    protected function creditAgentCommission(Client $client, Payment $payment, float $amount): void
    {
        if (!$client->agent_id) {
            return;
        }

        $agent = \App\Models\Agent::find($client->agent_id);
        if (!$agent || $agent->status !== 'active') {
            return;
        }

        $commissionPercent = $client->commission_percent !== null ? (float)$client->commission_percent : $agent->default_commission;
        if ($commissionPercent <= 0) {
            return;
        }

        $existing = \App\Models\AgentCommission::where('payment_id', $payment->id)
            ->where('type', 'commission')
            ->where('agent_id', $agent->id)
            ->exists();
        if ($existing) {
            return;
        }

        $commissionAmount = ($amount * $commissionPercent) / 100;
        $date = $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date) : now();

        \App\Models\AgentCommission::create([
            'agent_id' => $agent->id,
            'type' => 'commission',
            'client_id' => $client->id,
            'payment_id' => $payment->id,
            'client_name' => $client->name,
            'client_pppoe_id' => $client->username,
            'zone_address' => $client->zone ? $client->zone->name : ($client->address ?? null),
            'purpose' => 'Monthly Bill',
            'note' => 'Auto-credited from payment #' . ($payment->payment_no ?? $payment->id),
            'date' => $date->toDateString(),
            'payment_amount' => $amount,
            'commission_percent' => $commissionPercent,
            'commission_amount' => $commissionAmount,
            'month' => $date->month,
            'year' => $date->year,
            'company_id' => $client->company_id,
            'created_by' => $payment->collected_by,
        ]);
    }

    public static function permanentlyDeletePayments(array $paymentIds): void
    {
        if (empty($paymentIds)) return;

        DB::table('ledger_entries')->where('reference_type', 'payment')->whereIn('reference_id', $paymentIds)->delete();
        DB::table('payment_audit_trail')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('payment_corrections')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('payment_fraud_alerts')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('payment_gateway_transactions')->whereIn('id', function($q) use ($paymentIds) {
            $q->select('id')->from('payment_gateway_transactions')->whereIn('bill_id', function($q2) use ($paymentIds) {
                $q2->select('bill_id')->from('payments')->whereIn('id', $paymentIds);
            });
        })->delete();
        DB::table('collections')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('refunds')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('webhook_payment_logs')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('agent_commissions')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('sub_reseller_commissions')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('payment_links')->whereIn('payment_id', $paymentIds)->delete();
        DB::table('payments')->whereIn('id', $paymentIds)->delete();
    }

    public static function permanentlyDeleteBill(int $billId): void
    {
        $paymentIds = DB::table('payments')->where('bill_id', $billId)->pluck('id')->toArray();
        self::permanentlyDeletePayments($paymentIds);

        DB::table('ledger_entries')->where('reference_type', 'bill')->where('reference_id', $billId)->delete();
        DB::table('bill_items')->where('bill_id', $billId)->delete();
        DB::table('bill_adjustments')->where('bill_id', $billId)->delete();
        DB::table('bill_corrections')->where('bill_id', $billId)->delete();
        DB::table('bill_splits')->where('bill_id', $billId)->delete();
        DB::table('client_promo_usage')->where('bill_id', $billId)->delete();
        DB::table('due_reminders')->where('bill_id', $billId)->delete();
        DB::table('invoices')->where('bill_id', $billId)->delete();
        DB::table('payment_gateway_transactions')->where('bill_id', $billId)->delete();
        DB::table('payment_links')->where('bill_id', $billId)->delete();

        DB::table('bills')->where('id', $billId)->delete();
    }
}
