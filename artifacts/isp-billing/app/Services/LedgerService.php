<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\BillCorrection;
use App\Models\PaymentCorrection;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function createBillEntry(Bill $bill, ?int $userId = null, ?string $role = null): LedgerEntry
    {
        $runningBalance = $this->getClientBalance($bill->client_id) + $bill->total_amount;
        
        return LedgerEntry::create([
            'client_id' => $bill->client_id,
            'entry_type' => 'bill',
            'debit_credit' => 'debit',
            'amount' => $bill->total_amount,
            'running_balance' => $runningBalance,
            'reference_type' => 'Bill',
            'reference_id' => $bill->id,
            'description' => "Bill #{$bill->bill_no} for " . ($bill->billing_month ?? date('F Y')),
            'entry_date' => $bill->bill_date ?? now(),
            'billing_month' => $bill->billing_year . '-' . str_pad($bill->billing_month, 2, '0', STR_PAD_LEFT),
            'created_by' => $userId ?? auth()->id(),
            'created_by_role' => $role ?? (auth()->user()?->role ?? 'system'),
            'ip_address' => request()->ip(),
        ]);
    }

    public function createPaymentEntry(Payment $payment, ?int $userId = null, ?string $role = null): LedgerEntry
    {
        $runningBalance = $this->getClientBalance($payment->client_id) - $payment->amount;
        
        return LedgerEntry::create([
            'client_id' => $payment->client_id,
            'entry_type' => 'payment',
            'debit_credit' => 'credit',
            'amount' => $payment->amount,
            'running_balance' => $runningBalance,
            'reference_type' => 'Payment',
            'reference_id' => $payment->id,
            'description' => "Payment #{$payment->payment_no}" . ($payment->bill_id ? " for Bill #{$payment->bill->bill_no}" : ''),
            'entry_date' => $payment->payment_date ?? now(),
            'billing_month' => date('Y-m', strtotime($payment->payment_date ?? now())),
            'created_by' => $userId ?? auth()->id(),
            'created_by_role' => $role ?? (auth()->user()?->role ?? 'system'),
            'ip_address' => request()->ip(),
        ]);
    }

    public function createAdjustmentEntry(
        int $clientId,
        float $amount,
        string $debitCredit,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null
    ): LedgerEntry {
        $adjustment = $debitCredit === 'debit' ? $amount : -$amount;
        $runningBalance = $this->getClientBalance($clientId) + $adjustment;
        
        return LedgerEntry::create([
            'client_id' => $clientId,
            'entry_type' => 'adjustment',
            'debit_credit' => $debitCredit,
            'amount' => $amount,
            'running_balance' => $runningBalance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'entry_date' => now(),
            'billing_month' => date('Y-m'),
            'created_by' => $userId ?? auth()->id(),
            'created_by_role' => auth()->user()?->role ?? 'system',
            'ip_address' => request()->ip(),
        ]);
    }

    public function createReversalEntry(LedgerEntry $originalEntry, string $reason, ?int $userId = null): LedgerEntry
    {
        $reverseDebitCredit = $originalEntry->debit_credit === 'debit' ? 'credit' : 'debit';
        $adjustment = $reverseDebitCredit === 'debit' ? $originalEntry->amount : -$originalEntry->amount;
        $runningBalance = $this->getClientBalance($originalEntry->client_id) + $adjustment;
        
        return LedgerEntry::create([
            'client_id' => $originalEntry->client_id,
            'entry_type' => 'reversal',
            'debit_credit' => $reverseDebitCredit,
            'amount' => $originalEntry->amount,
            'running_balance' => $runningBalance,
            'reference_type' => $originalEntry->reference_type,
            'reference_id' => $originalEntry->reference_id,
            'description' => "REVERSAL: {$reason} (Original: {$originalEntry->description})",
            'entry_date' => now(),
            'billing_month' => date('Y-m'),
            'created_by' => $userId ?? auth()->id(),
            'created_by_role' => auth()->user()?->role ?? 'system',
            'ip_address' => request()->ip(),
            'is_reversal' => true,
            'reverses_entry_id' => $originalEntry->id,
        ]);
    }

    public function createCorrectionEntry(
        int $clientId,
        float $amount,
        string $debitCredit,
        string $description,
        string $referenceType,
        int $referenceId,
        ?int $userId = null
    ): LedgerEntry {
        $adjustment = $debitCredit === 'debit' ? $amount : -$amount;
        $runningBalance = $this->getClientBalance($clientId) + $adjustment;
        
        return LedgerEntry::create([
            'client_id' => $clientId,
            'entry_type' => 'correction',
            'debit_credit' => $debitCredit,
            'amount' => $amount,
            'running_balance' => $runningBalance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'entry_date' => now(),
            'billing_month' => date('Y-m'),
            'created_by' => $userId ?? auth()->id(),
            'created_by_role' => auth()->user()?->role ?? 'system',
            'ip_address' => request()->ip(),
        ]);
    }

    public function getClientBalance(int $clientId): float
    {
        $totals = LedgerEntry::where('client_id', $clientId)
            ->selectRaw('
                SUM(CASE WHEN debit_credit = \'debit\' THEN amount ELSE 0 END) as total_debit,
                SUM(CASE WHEN debit_credit = \'credit\' THEN amount ELSE 0 END) as total_credit
            ')
            ->first();

        $totalDebit = (float) ($totals->total_debit ?? 0);
        $totalCredit = (float) ($totals->total_credit ?? 0);
        
        return round($totalDebit - $totalCredit, 2);
    }

    public function getClientLedger(int $clientId, ?string $fromDate = null, ?string $toDate = null)
    {
        $query = LedgerEntry::where('client_id', $clientId)
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        if ($fromDate) {
            $query->where('entry_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('entry_date', '<=', $toDate);
        }

        return $query->get();
    }

    public function recalculateRunningBalances(int $clientId): void
    {
        DB::transaction(function () use ($clientId) {
            $entries = LedgerEntry::where('client_id', $clientId)
                ->orderBy('entry_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $runningBalance = 0;
            foreach ($entries as $entry) {
                if ($entry->debit_credit === 'debit') {
                    $runningBalance += $entry->amount;
                } else {
                    $runningBalance -= $entry->amount;
                }
                $entry->update(['running_balance' => $runningBalance]);
            }
        });
    }

    public function syncExistingBillsToLedger(int $clientId): int
    {
        $count = 0;
        
        DB::transaction(function () use ($clientId, &$count) {
            $bills = Bill::where('client_id', $clientId)
                ->whereNotIn('id', function ($query) {
                    $query->select('reference_id')
                        ->from('ledger_entries')
                        ->where('reference_type', 'Bill');
                })
                ->orderBy('bill_date', 'asc')
                ->get();

            foreach ($bills as $bill) {
                if (!$bill->is_cancelled) {
                    $this->createBillEntry($bill);
                    $count++;
                }
            }
        });

        return $count;
    }

    public function syncExistingPaymentsToLedger(int $clientId): int
    {
        $count = 0;
        
        DB::transaction(function () use ($clientId, &$count) {
            $payments = Payment::where('client_id', $clientId)
                ->whereNotIn('id', function ($query) {
                    $query->select('reference_id')
                        ->from('ledger_entries')
                        ->where('reference_type', 'Payment');
                })
                ->orderBy('payment_date', 'asc')
                ->get();

            foreach ($payments as $payment) {
                if (!$payment->is_reversed && !$payment->is_duplicate) {
                    $this->createPaymentEntry($payment);
                    $count++;
                }
            }
        });

        return $count;
    }

    public function getMonthlyStatement(int $clientId, int $year, int $month): array
    {
        $billingMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        
        $entries = LedgerEntry::where('client_id', $clientId)
            ->where('billing_month', $billingMonth)
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $openingBalance = LedgerEntry::where('client_id', $clientId)
            ->where('entry_date', '<', "{$year}-{$month}-01")
            ->selectRaw('
                SUM(CASE WHEN debit_credit = \'debit\' THEN amount ELSE 0 END) -
                SUM(CASE WHEN debit_credit = \'credit\' THEN amount ELSE 0 END) as balance
            ')
            ->value('balance') ?? 0;

        $totalDebits = $entries->where('debit_credit', 'debit')->sum('amount');
        $totalCredits = $entries->where('debit_credit', 'credit')->sum('amount');
        $closingBalance = $openingBalance + $totalDebits - $totalCredits;

        return [
            'billing_month' => $billingMonth,
            'opening_balance' => round($openingBalance, 2),
            'total_debits' => round($totalDebits, 2),
            'total_credits' => round($totalCredits, 2),
            'closing_balance' => round($closingBalance, 2),
            'entries' => $entries,
        ];
    }
}
