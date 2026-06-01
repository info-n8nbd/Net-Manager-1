<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CreditDebitNote;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditDebitNoteService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function createNote(
        int $clientId,
        string $type,
        float $amount,
        string $reason,
        ?string $description,
        int $billingMonth,
        int $billingYear,
        int $createdBy
    ): CreditDebitNote {
        DB::beginTransaction();
        try {
            $note = CreditDebitNote::create([
                'client_id' => $clientId,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
                'description' => $description,
                'billing_month' => $billingMonth,
                'billing_year' => $billingYear,
                'status' => 'pending',
                'created_by' => $createdBy,
            ]);

            Log::info('Credit/Debit note created', [
                'note_id' => $note->id,
                'client_id' => $clientId,
                'type' => $type,
                'amount' => $amount,
                'created_by' => $createdBy,
            ]);

            DB::commit();
            return $note;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approveNote(int $noteId, int $approvedBy): CreditDebitNote
    {
        DB::beginTransaction();
        try {
            $note = CreditDebitNote::lockForUpdate()->findOrFail($noteId);

            if ($note->status !== 'pending') {
                throw new \Exception('Note is not in pending status');
            }

            $client = Client::lockForUpdate()->findOrFail($note->client_id);

            if ($note->type === 'credit') {
                $originalDue = $client->due_amount ?? 0;
                $creditAmount = $note->amount;
                
                if ($creditAmount >= $originalDue) {
                    $client->due_amount = 0;
                    $excessCredit = $creditAmount - $originalDue;
                    if ($excessCredit > 0) {
                        $client->balance = ($client->balance ?? 0) + $excessCredit;
                    }
                } else {
                    $client->due_amount = $originalDue - $creditAmount;
                }
            } else {
                $client->due_amount = ($client->due_amount ?? 0) + $note->amount;
            }
            $client->save();

            $debitCredit = $note->type === 'credit' ? 'credit' : 'debit';
            $ledgerEntry = $this->ledgerService->createAdjustmentEntry(
                $note->client_id,
                $note->amount,
                $debitCredit,
                "{$note->type} note: {$note->reason}",
                'CreditDebitNote',
                $note->id,
                $approvedBy
            );

            $note->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'ledger_entry_id' => $ledgerEntry->id,
            ]);

            Log::info('Credit/Debit note approved', [
                'note_id' => $note->id,
                'approved_by' => $approvedBy,
            ]);

            DB::commit();
            return $note->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rejectNote(int $noteId, int $rejectedBy, ?string $rejectionReason = null): CreditDebitNote
    {
        $note = CreditDebitNote::findOrFail($noteId);

        if ($note->status !== 'pending') {
            throw new \Exception('Note is not in pending status');
        }

        $note->update([
            'status' => 'rejected',
            'description' => $note->description . "\n[Rejected]: " . ($rejectionReason ?? 'No reason provided'),
        ]);

        Log::info('Credit/Debit note rejected', [
            'note_id' => $note->id,
            'rejected_by' => $rejectedBy,
            'reason' => $rejectionReason,
        ]);

        return $note;
    }

    public function getClientNotes(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return CreditDebitNote::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPendingNotes(): \Illuminate\Database\Eloquent\Collection
    {
        return CreditDebitNote::where('status', 'pending')
            ->with(['client', 'createdBy'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
