<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\MonthClose;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonthCloseService
{
    public function getMonthStatus(int $month, int $year): array
    {
        $monthClose = MonthClose::where('billing_month', $month)
            ->where('billing_year', $year)
            ->first();

        if (!$monthClose) {
            return [
                'status' => 'open',
                'can_modify' => true,
                'summary' => $this->calculateMonthSummary($month, $year),
            ];
        }

        return [
            'status' => $monthClose->status,
            'can_modify' => $monthClose->status === 'open',
            'closed_at' => $monthClose->closed_at,
            'closed_by' => $monthClose->closedBy?->name,
            'summary' => [
                'total_billed' => $monthClose->total_billed,
                'total_collected' => $monthClose->total_collected,
                'total_due' => $monthClose->total_due,
                'total_advance' => $monthClose->total_advance,
                'active_clients' => $monthClose->active_clients,
                'new_connections' => $monthClose->new_connections,
                'disconnections' => $monthClose->disconnections,
            ],
        ];
    }

    public function calculateMonthSummary(int $month, int $year): array
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $totalBilled = Bill::where('billing_month', $month)
            ->where('billing_year', $year)
            ->sum('total_amount');

        $totalCollected = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->sum('amount');

        $totalDue = Bill::where('billing_month', $month)
            ->where('billing_year', $year)
            ->sum('due_amount');

        $totalAdvance = Client::where('balance', '>', 0)->sum('balance');

        $activeClients = Client::where('status', 'active')->count();

        $newConnections = Client::whereBetween('connection_date', [$startDate, $endDate])->count();

        $disconnections = Client::where('status', 'inactive')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        return [
            'total_billed' => $totalBilled,
            'total_collected' => $totalCollected,
            'total_due' => $totalDue,
            'total_advance' => $totalAdvance,
            'active_clients' => $activeClients,
            'new_connections' => $newConnections,
            'disconnections' => $disconnections,
        ];
    }

    public function closeMonth(int $month, int $year, int $userId, ?string $notes = null): MonthClose
    {
        $existing = MonthClose::where('billing_month', $month)
            ->where('billing_year', $year)
            ->first();

        if ($existing && $existing->status !== 'open') {
            throw new \Exception('Month is already closed or locked');
        }

        $summary = $this->calculateMonthSummary($month, $year);

        DB::beginTransaction();
        try {
            $monthClose = MonthClose::updateOrCreate(
                ['billing_month' => $month, 'billing_year' => $year],
                [
                    'status' => 'closed',
                    'total_billed' => $summary['total_billed'],
                    'total_collected' => $summary['total_collected'],
                    'total_due' => $summary['total_due'],
                    'total_advance' => $summary['total_advance'],
                    'active_clients' => $summary['active_clients'],
                    'new_connections' => $summary['new_connections'],
                    'disconnections' => $summary['disconnections'],
                    'closed_by' => $userId,
                    'closed_at' => now(),
                    'notes' => $notes,
                    'summary_data' => $summary,
                ]
            );

            Log::info('Month closed', [
                'month' => $month,
                'year' => $year,
                'closed_by' => $userId,
            ]);

            DB::commit();
            return $monthClose;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function lockMonth(int $month, int $year, int $userId): MonthClose
    {
        $monthClose = MonthClose::where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('status', 'closed')
            ->firstOrFail();

        $monthClose->update(['status' => 'locked']);

        Log::info('Month locked', [
            'month' => $month,
            'year' => $year,
            'locked_by' => $userId,
        ]);

        return $monthClose;
    }

    public function reopenMonth(int $month, int $year, int $userId, string $reason): MonthClose
    {
        $monthClose = MonthClose::where('billing_month', $month)
            ->where('billing_year', $year)
            ->firstOrFail();

        if ($monthClose->status === 'locked') {
            throw new \Exception('Cannot reopen a locked month');
        }

        $monthClose->update([
            'status' => 'open',
            'notes' => $monthClose->notes . "\n[Reopened by user {$userId}]: {$reason}",
        ]);

        Log::warning('Month reopened', [
            'month' => $month,
            'year' => $year,
            'reopened_by' => $userId,
            'reason' => $reason,
        ]);

        return $monthClose;
    }

    public function canModifyBill(Bill $bill): bool
    {
        return !MonthClose::isMonthClosed($bill->billing_month, $bill->billing_year);
    }
}
