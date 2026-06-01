<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyCashClose;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\StaffDue;
use App\Models\User;
use App\Services\DailyCashCloseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffManagementController extends Controller
{
    protected DailyCashCloseService $dailyCashService;

    public function __construct(DailyCashCloseService $dailyCashService)
    {
        $this->dailyCashService = $dailyCashService;
    }

    public function dailyClose(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        $pendingCloses = $this->dailyCashService->getPendingCloses();

        $staffWithCollections = User::whereIn('user_type', ['technician', 'billman'])
            ->whereHas('collectedPayments', function ($q) use ($date) {
                $q->whereDate('payment_date', $date);
            })
            ->with(['collectedPayments' => function ($q) use ($date) {
                $q->whereDate('payment_date', $date);
            }])
            ->get()
            ->map(function ($staff) use ($date) {
                $summary = $this->dailyCashService->getDailySummary($staff->id, $date);
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'role' => $staff->user_type,
                    'summary' => $summary,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'date' => $date,
                'staff' => $staffWithCollections,
                'pending_closes' => $pendingCloses,
            ]);
        }

        return view('admin.staff.daily-close', compact('date', 'staffWithCollections', 'pendingCloses'));
    }

    public function staffDailySummary(Request $request, $staffId)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $summary = $this->dailyCashService->getDailySummary($staffId, $date);
        $staff = User::findOrFail($staffId);

        return response()->json([
            'success' => true,
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
            ],
            'summary' => $summary,
        ]);
    }

    public function verifyDailyClose(Request $request, $staffId)
    {
        $request->validate([
            'close_id' => 'required|exists:daily_cash_closes,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $close = $this->dailyCashService->verifyDailyClose(
                $request->close_id,
                auth()->id(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Daily close verified successfully',
                'data' => $close,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function outstandingReport(Request $request)
    {
        $staffOutstanding = StaffDue::with('user')
            ->where('current_due', '>', 0)
            ->orderBy('current_due', 'desc')
            ->get()
            ->map(function ($due) {
                return [
                    'staff_id' => $due->user_id,
                    'staff_name' => $due->user?->name ?? 'Unknown',
                    'role' => $due->user?->user_type ?? 'Unknown',
                    'total_collected' => $due->total_collected,
                    'total_submitted' => $due->total_submitted,
                    'current_due' => $due->current_due,
                    'last_collection' => $due->last_collection_date,
                    'outstanding' => $this->dailyCashService->getStaffOutstanding($due->user_id),
                ];
            });

        $totalOutstanding = $staffOutstanding->sum('current_due');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'staff' => $staffOutstanding,
                'total_outstanding' => $totalOutstanding,
            ]);
        }

        return view('admin.staff.outstanding', compact('staffOutstanding', 'totalOutstanding'));
    }

    public function areaCollection(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $areawise = $this->dailyCashService->getAreawiseCollection($date);

        $dateRange = $request->get('range', 'day');
        if ($dateRange === 'month') {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        } elseif ($dateRange === 'week') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } else {
            $startDate = $date;
            $endDate = $date;
        }

        $collections = DB::table('payments')
            ->join('clients', 'payments.client_id', '=', 'clients.id')
            ->join('zones', 'clients.zone_id', '=', 'zones.id')
            ->whereBetween('payments.payment_date', [$startDate, $endDate])
            ->select(
                'zones.id as zone_id',
                'zones.name as zone_name',
                DB::raw('COUNT(payments.id) as payment_count'),
                DB::raw('SUM(payments.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT payments.collected_by) as collectors')
            )
            ->groupBy('zones.id', 'zones.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'date' => $date,
                'range' => $dateRange,
                'collections' => $collections,
            ]);
        }

        return view('admin.staff.area-collection', compact('date', 'dateRange', 'collections'));
    }

    public function kpiDashboard(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $staffKpi = User::whereIn('user_type', ['technician', 'billman'])
            ->with(['employee'])
            ->get()
            ->map(function ($staff) use ($startDate, $endDate) {
                $collections = Payment::where('collected_by', $staff->id)
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->get();

                $totalCollected = $collections->sum('amount');
                $collectionCount = $collections->count();

                $ticketsResolved = DB::table('tickets')
                    ->where('technician_id', $staff->id)
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->count();

                $avgResolutionTime = DB::table('tickets')
                    ->where('technician_id', $staff->id)
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->whereNotNull('resolved_at')
                    ->avg(DB::raw('EXTRACT(EPOCH FROM (resolved_at - created_at)) / 3600'));

                return [
                    'staff_id' => $staff->id,
                    'name' => $staff->name,
                    'role' => $staff->user_type,
                    'zone' => $staff->employee?->zone_ids ?? [],
                    'kpi' => [
                        'total_collected' => $totalCollected,
                        'collection_count' => $collectionCount,
                        'avg_collection' => $collectionCount > 0 ? $totalCollected / $collectionCount : 0,
                        'tickets_resolved' => $ticketsResolved,
                        'avg_resolution_hours' => round($avgResolutionTime ?? 0, 2),
                    ],
                ];
            })
            ->sortByDesc(fn($s) => $s['kpi']['total_collected'])
            ->values();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'month' => $month,
                'year' => $year,
                'staff_kpi' => $staffKpi,
            ]);
        }

        return view('admin.staff.kpi', compact('month', 'year', 'staffKpi'));
    }
}
