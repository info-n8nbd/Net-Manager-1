<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentCorrection;
use App\Models\CorrectionAuditLog;
use App\Models\LedgerEntry;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentCorrectionService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function detectDuplicateByTrxId(string $transactionId, int $clientId): ?Payment
    {
        if (empty($transactionId)) {
            return null;
        }

        return Payment::where('transaction_id', $transactionId)
            ->where('client_id', $clientId)
            ->where('is_reversed', false)
            ->where('is_duplicate', false)
            ->first();
    }

    public function detectDuplicateByAmountTime(int $clientId, float $amount, int $windowMinutes = 5): ?Payment
    {
        $cutoffTime = now()->subMinutes($windowMinutes);
        
        return Payment::where('client_id', $clientId)
            ->where('amount', $amount)
            ->where('created_at', '>=', $cutoffTime)
            ->where('is_reversed', false)
            ->where('is_duplicate', false)
            ->first();
    }

    public function checkForDuplicates(int $clientId, float $amount, ?string $transactionId = null): array
    {
        $duplicates = [];

        if ($transactionId) {
            $trxDuplicate = $this->detectDuplicateByTrxId($transactionId, $clientId);
            if ($trxDuplicate) {
                $duplicates['transaction_id'] = $trxDuplicate;
            }
        }

        $timeDuplicate = $this->detectDuplicateByAmountTime($clientId, $amount);
        if ($timeDuplicate) {
            $duplicates['recent_same_amount'] = $timeDuplicate;
        }

        return $duplicates;
    }

    protected function validateReason(string $reason): void
    {
        $trimmed = trim($reason);
        if (empty($trimmed)) {
            throw new Exception('Correction reason is mandatory');
        }
        if (strlen($trimmed) < 10) {
            throw new Exception('Correction reason must be at least 10 characters');
        }
    }

    public function markAsDuplicate(Payment $payment, Payment $originalPayment, string $reason): PaymentCorrection
    {
        $this->validateReason($reason);

        return DB::transaction(function () use ($payment, $originalPayment, $reason) {
            $correction = PaymentCorrection::create([
                'payment_id' => $payment->id,
                'client_id' => $payment->client_id,
                'correction_type' => 'duplicate',
                'status' => 'approved',
                'original_amount' => $payment->amount,
                'original_bill_id' => $payment->bill_id,
                'original_transaction_id' => $payment->transaction_id,
                'duplicate_of_payment_id' => $originalPayment->id,
                'duplicate_reason' => $reason,
                'reason' => $reason,
                'requested_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            $beforeState = $payment->toArray();

            $payment->update([
                'is_duplicate' => true,
                'duplicate_of_id' => $originalPayment->id,
                'status' => 'duplicate',
            ]);

            $originalEntry = LedgerEntry::where('reference_type', 'Payment')
                ->where('reference_id', $payment->id)
                ->where('entry_type', 'payment')
                ->first();

            if ($originalEntry) {
                $this->ledgerService->createReversalEntry(
                    $originalEntry,
                    "Duplicate Payment #{$correction->correction_no}: {$reason}"
                );
            } else {
                $this->ledgerService->createCorrectionEntry(
                    $payment->client_id,
                    $payment->amount,
                    'debit',
                    "Duplicate Payment #{$correction->correction_no}: {$reason}",
                    'PaymentCorrection',
                    $correction->id
                );
            }

            $this->recalculateBillAndBalance($payment);

            CorrectionAuditLog::log(
                'PaymentCorrection',
                $correction->id,
                'approved',
                $beforeState,
                $payment->fresh()->toArray()
            );

            return $correction;
        });
    }

    public function reversePayment(Payment $payment, string $reason): PaymentCorrection
    {
        $this->validateReason($reason);

        if ($payment->is_reversed) {
            throw new Exception('Payment is already reversed');
        }

        return DB::transaction(function () use ($payment, $reason) {
            $correction = PaymentCorrection::create([
                'payment_id' => $payment->id,
                'client_id' => $payment->client_id,
                'correction_type' => 'reversal',
                'status' => 'approved',
                'original_amount' => $payment->amount,
                'original_bill_id' => $payment->bill_id,
                'original_transaction_id' => $payment->transaction_id,
                'reversal_amount' => $payment->amount,
                'reason' => $reason,
                'requested_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            $beforeState = $payment->toArray();

            $payment->update([
                'is_reversed' => true,
                'status' => 'reversed',
            ]);

            $originalEntry = LedgerEntry::where('reference_type', 'Payment')
                ->where('reference_id', $payment->id)
                ->where('entry_type', 'payment')
                ->first();

            if ($originalEntry) {
                $this->ledgerService->createReversalEntry(
                    $originalEntry,
                    "Payment Reversal #{$correction->correction_no}: {$reason}"
                );
            } else {
                $this->ledgerService->createCorrectionEntry(
                    $payment->client_id,
                    $payment->amount,
                    'debit',
                    "Payment Reversal #{$correction->correction_no}: {$reason}",
                    'PaymentCorrection',
                    $correction->id
                );
            }

            $this->recalculateBillAndBalance($payment);

            CorrectionAuditLog::log(
                'PaymentCorrection',
                $correction->id,
                'approved',
                $beforeState,
                $payment->fresh()->toArray()
            );

            return $correction;
        });
    }

    protected function recalculateBillAndBalance(Payment $payment): void
    {
        if ($payment->bill_id) {
            $bill = Bill::find($payment->bill_id);
            if ($bill) {
                $validPayments = Payment::where('bill_id', $bill->id)
                    ->where('status', 'approved')
                    ->where('is_reversed', false)
                    ->where('is_duplicate', false)
                    ->sum('amount');
                
                $bill->update([
                    'paid_amount' => $validPayments,
                    'due_amount' => max(0, $bill->total_amount - $validPayments),
                    'status' => $validPayments >= $bill->total_amount ? 'paid' : ($validPayments > 0 ? 'partial' : 'unpaid'),
                ]);
            }
        }

        $billingService = app(BillingService::class);
        $billingService->recalculateClientBalance($payment->client);
    }

    public function getPaymentCorrectionHistory(Payment $payment)
    {
        return PaymentCorrection::where('payment_id', $payment->id)
            ->with(['requestedBy', 'approvedBy', 'duplicateOfPayment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getClientPaymentCorrectionHistory(int $clientId)
    {
        return PaymentCorrection::where('client_id', $clientId)
            ->with(['payment', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
