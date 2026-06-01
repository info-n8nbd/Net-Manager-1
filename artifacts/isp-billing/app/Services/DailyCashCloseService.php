<?php

namespace App\Services;

use App\Models\DailyCashClose;
use App\Models\Payment;
use App\Models\StaffDue;
use App\Models\StaffSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyCashCloseService
{
    public function getDailySummary(int $staffId, $date = null): array
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $dateStr = $date->format('Y-m-d');

        $openingBalance = DailyCashClose::getOpeningBalance($staffId, $dateStr);

        $totalCollection = Payment::where('collected_by', $staffId)
            ->whereDate('payment_date', $dateStr)
            ->sum('amount');

        $totalSubmitted = StaffSubmission::where('staff_user_id', $staffId)
            ->whereDate('submission_date', $dateStr)
            ->where('status', 'received')
            ->sum('amount');

        $closingBalance = $openingBalance + $totalCollection - $totalSubmitted;

        $existingClose = DailyCashClose::where('staff_user_id', $staffId)
            ->where('close_date', $dateStr)
            ->first();

        return [
            'date' => $dateStr,
            'opening_balance' => $openingBalance,
            'total_collection' => $totalCollection,
            'total_submitted' => $totalSubmitted,
            'closing_balance' => $closingBalance,
            'existing_close' => $existingClose,
            'can_close' => !$existingClose || $existingClose->status === 'pending',
        ];
    }

    public function closeDailyCollection(
        int $staffId,
        $date,
        float $cashInHand,
        ?string $notes = null
    ): DailyCashClose {
        $date = \Carbon\Carbon::parse($date);
        $dateStr = $date->format('Y-m-d');

        $summary = $this->getDailySummary($staffId, $date);

        if ($summary['existing_close'] && $summary['existing_close']->status !== 'pending') {
            throw new \Exception('Daily close already verified for this date');
        }

        $discrepancy = $cashInHand - $summary['closing_balance'];

        DB::beginTransaction();
        try {
            $dailyClose = DailyCashClose::updateOrCreate(
                ['close_date' => $dateStr, 'staff_user_id' => $staffId],
                [
                    'opening_balance' => $summary['opening_balance'],
                    'total_collection' => $summary['total_collection'],
                    'total_submitted' => $summary['total_submitted'],
                    'closing_balance' => $summary['closing_balance'],
                    'cash_in_hand' => $cashInHand,
                    'discrepancy' => $discrepancy,
                    'status' => abs($discrepancy) > 0.01 ? 'discrepancy' : 'pending',
                    'notes' => $notes,
                ]
            );

            Log::info('Daily cash close submitted', [
                'staff_id' => $staffId,
                'date' => $dateStr,
                'closing_balance' => $summary['closing_balance'],
                'cash_in_hand' => $cashInHand,
                'discrepancy' => $discrepancy,
            ]);

            DB::commit();
            return $dailyClose;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function verifyDailyClose(int $closeId, int $verifiedBy, ?string $notes = null): DailyCashClose
    {
        $dailyClose = DailyCashClose::findOrFail($closeId);

        $dailyClose->update([
            'status' => 'verified',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'notes' => $dailyClose->notes . ($notes ? "\n[Verified]: {$notes}" : ''),
        ]);

        Log::info('Daily cash close verified', [
            'close_id' => $closeId,
            'verified_by' => $verifiedBy,
        ]);

        return $dailyClose;
    }

    public function getStaffOutstanding(int $staffId): array
    {
        $staffDue = StaffDue::where('user_id', $staffId)->first();

        $lastClose = DailyCashClose::where('staff_user_id', $staffId)
            ->orderBy('close_date', 'desc')
            ->first();

        $pendingCollections = Payment::where('collected_by', $staffId)
            ->where('submitted_to_admin', false)
            ->sum('amount');

        return [
            'current_due' => $staffDue?->current_due ?? 0,
            'total_collected' => $staffDue?->total_collected ?? 0,
            'total_submitted' => $staffDue?->total_submitted ?? 0,
            'last_close_date' => $lastClose?->close_date,
            'last_close_balance' => $lastClose?->closing_balance ?? 0,
            'pending_collections' => $pendingCollections,
        ];
    }

    public function getAreawiseCollection($date = null): array
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();

        return DB::table('payments')
            ->join('clients', 'payments.client_id', '=', 'clients.id')
            ->join('zones', 'clients.zone_id', '=', 'zones.id')
            ->whereDate('payments.payment_date', $date)
            ->select(
                'zones.id as zone_id',
                'zones.name as zone_name',
                DB::raw('COUNT(payments.id) as payment_count'),
                DB::raw('SUM(payments.amount) as total_amount')
            )
            ->groupBy('zones.id', 'zones.name')
            ->orderBy('zones.name')
            ->get()
            ->toArray();
    }

    public function getPendingCloses(): \Illuminate\Database\Eloquent\Collection
    {
        return DailyCashClose::where('status', 'pending')
            ->orWhere('status', 'discrepancy')
            ->with('staff')
            ->orderBy('close_date', 'desc')
            ->get();
    }
}
