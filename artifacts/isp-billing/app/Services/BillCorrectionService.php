<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillCorrection;
use App\Models\CorrectionAuditLog;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Exception;

class BillCorrectionService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
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

    public function correctAmount(Bill $bill, float $newPackageAmount, float $newDiscount, string $reason): BillCorrection
    {
        $this->validateReason($reason);

        return DB::transaction(function () use ($bill, $newPackageAmount, $newDiscount, $reason) {
            $newTotalAmount = $newPackageAmount + ($bill->previous_due ?? 0) - $newDiscount;
            $adjustmentAmount = $newTotalAmount - $bill->total_amount;

            $correction = BillCorrection::create([
                'bill_id' => $bill->id,
                'client_id' => $bill->client_id,
                'correction_type' => 'amount_correction',
                'status' => 'approved',
                'original_package_amount' => $bill->package_amount,
                'original_discount' => $bill->discount ?? 0,
                'original_total_amount' => $bill->total_amount,
                'original_due_amount' => $bill->due_amount,
                'original_status' => $bill->status,
                'new_package_amount' => $newPackageAmount,
                'new_discount' => $newDiscount,
                'new_total_amount' => $newTotalAmount,
                'adjustment_amount' => $adjustmentAmount,
                'reason' => $reason,
                'requested_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            $beforeState = $bill->toArray();

            $bill->update([
                'package_amount' => $newPackageAmount,
                'discount' => $newDiscount,
                'total_amount' => $newTotalAmount,
                'due_amount' => $newTotalAmount - $bill->paid_amount,
                'is_corrected' => true,
            ]);

            if ($adjustmentAmount != 0) {
                $debitCredit = $adjustmentAmount > 0 ? 'debit' : 'credit';
                $this->ledgerService->createCorrectionEntry(
                    $bill->client_id,
                    abs($adjustmentAmount),
                    $debitCredit,
                    "Bill Correction #{$correction->correction_no}: {$reason}",
                    'BillCorrection',
                    $correction->id
                );
            }

            CorrectionAuditLog::log(
                'BillCorrection',
                $correction->id,
                'approved',
                $beforeState,
                $bill->fresh()->toArray()
            );

            return $correction;
        });
    }

    public function cancelBill(Bill $bill, string $reason): BillCorrection
    {
        $this->validateReason($reason);

        if ($bill->paid_amount > 0) {
            throw new Exception('Cannot cancel a bill with payments. Reverse payments first.');
        }

        return DB::transaction(function () use ($bill, $reason) {
            $correction = BillCorrection::create([
                'bill_id' => $bill->id,
                'client_id' => $bill->client_id,
                'correction_type' => 'cancellation',
                'status' => 'approved',
                'original_package_amount' => $bill->package_amount,
                'original_discount' => $bill->discount ?? 0,
                'original_total_amount' => $bill->total_amount,
                'original_due_amount' => $bill->due_amount,
                'original_status' => $bill->status,
                'adjustment_amount' => -$bill->total_amount,
                'reason' => $reason,
                'requested_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            $beforeState = $bill->toArray();

            $bill->update([
                'is_cancelled' => true,
                'status' => 'cancelled',
            ]);

            $originalEntry = LedgerEntry::where('reference_type', 'Bill')
                ->where('reference_id', $bill->id)
                ->where('entry_type', 'bill')
                ->first();

            if ($originalEntry) {
                $this->ledgerService->createReversalEntry(
                    $originalEntry,
                    "Bill Cancellation #{$correction->correction_no}: {$reason}"
                );
            } else {
                $this->ledgerService->createCorrectionEntry(
                    $bill->client_id,
                    $bill->total_amount,
                    'credit',
                    "Bill Cancellation #{$correction->correction_no}: {$reason}",
                    'BillCorrection',
                    $correction->id
                );
            }

            CorrectionAuditLog::log(
                'BillCorrection',
                $correction->id,
                'approved',
                $beforeState,
                $bill->fresh()->toArray()
            );

            return $correction;
        });
    }

    public function replaceBill(Bill $oldBill, array $newBillData, string $reason): array
    {
        if (empty(trim($reason))) {
            throw new Exception('Replacement reason is mandatory');
        }

        return DB::transaction(function () use ($oldBill, $newBillData, $reason) {
            $cancelCorrection = $this->cancelBill($oldBill, "Replaced: {$reason}");

            $newBill = Bill::create(array_merge($newBillData, [
                'client_id' => $oldBill->client_id,
                'replaces_bill_id' => $oldBill->id,
            ]));

            $oldBill->update([
                'is_replaced' => true,
                'replaced_by_bill_id' => $newBill->id,
            ]);

            $cancelCorrection->update([
                'correction_type' => 'replacement',
                'replacement_bill_id' => $newBill->id,
            ]);

            $this->ledgerService->createBillEntry($newBill);

            return [
                'correction' => $cancelCorrection,
                'new_bill' => $newBill,
            ];
        });
    }

    public function getBillCorrectionHistory(Bill $bill)
    {
        return BillCorrection::where('bill_id', $bill->id)
            ->with(['requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getClientCorrectionHistory(int $clientId)
    {
        return BillCorrection::where('client_id', $clientId)
            ->with(['bill', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
