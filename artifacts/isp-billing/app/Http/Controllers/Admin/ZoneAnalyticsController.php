<?php
/**
 * Zone-wise Analytics and Problem Graphs Controller
 * 
 * Provides visual analytics for zone-based troubleshooting and management
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ZoneAnalyticsController extends Controller
{
    /**
     * Zone Analytics Dashboard
     */
    public function index()
    {
        $zones = Zone::withCount([
            'clients',
            'clients as active_clients_count' => function ($query) {
                $query->where('status', 'active');
            },
            'clients as inactive_clients_count' => function ($query) {
                $query->where('status', 'inactive');
            },
            'clients as due_clients_count' => function ($query) {
                $query->where('due_amount', '>', 0);
            },
        ])->where('is_active', true)->get();

        return view('admin.reports.zone-analytics.index', compact('zones'));
    }

    /**
     * Get Zone Problem Statistics (AJAX)
     */
    public function getZoneProblems(Request $request)
    {
        $zoneId = $request->zone_id;
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');

        $query = $zoneId 
            ? Zone::where('id', $zoneId)
            : Zone::where('is_active', true);

        $zones = $query->get()->map(function ($zone) use ($fromDate, $toDate) {
            // Count complaints/tickets by zone
            $ticketCount = Ticket::whereHas('client', function ($q) use ($zone) {
                    $q->where('zone_id', $zone->id);
                })
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->count();

            $inactiveCount = Client::where('zone_id', $zone->id)
                ->where('status', 'inactive')
                ->count();

            // Count overdue clients
            $overdueCount = Client::where('zone_id', $zone->id)
                ->where('due_amount', '>', 0)
                ->count();

            // Count offline clients (if MikroTik sync is available)
            $offlineCount = Client::where('zone_id', $zone->id)
                ->where('connection_status', 'offline')
                ->where('status', 'active')
                ->count();

            return [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'total_clients' => $zone->clients()->count(),
                'active_clients' => $zone->clients()->where('status', 'active')->count(),
                'ticket_count' => $ticketCount,
                'inactive_count' => $inactiveCount,
                'overdue_count' => $overdueCount,
                'offline_count' => $offlineCount,
                'problem_score' => $ticketCount + $inactiveCount + $overdueCount + $offlineCount,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $zones,
            'period' => "{$fromDate} to {$toDate}",
        ]);
    }

    /**
     * Get Collection Statistics by Zone (for graphs)
     */
    public function getZoneCollection(Request $request)
    {
        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $data = Zone::where('is_active', true)->get()->map(function ($zone) use ($startDate, $endDate) {
            $totalBilled = Bill::whereHas('client', function ($q) use ($zone) {
                    $q->where('zone_id', $zone->id);
                })
                ->whereMonth('bill_date', $startDate->month)
                ->whereYear('bill_date', $startDate->year)
                ->sum('total_amount');

            $totalCollected = Payment::whereHas('client', function ($q) use ($zone) {
                    $q->where('zone_id', $zone->id);
                })
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('amount');

            $collectionRate = $totalBilled > 0 
                ? round(($totalCollected / $totalBilled) * 100, 1) 
                : 0;

            return [
                'zone_name' => $zone->name,
                'total_billed' => $totalBilled,
                'total_collected' => $totalCollected,
                'pending' => $totalBilled - $totalCollected,
                'collection_rate' => $collectionRate,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => $startDate->format('F Y'),
        ]);
    }

    /**
     * Get Client Status Distribution by Zone (for pie charts)
     */
    public function getClientDistribution(Request $request)
    {
        $zoneId = $request->zone_id;

        $query = Client::query();
        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        $distribution = $query->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $connectionTypes = Client::query();
        if ($zoneId) {
            $connectionTypes->where('zone_id', $zoneId);
        }

        $typeDistribution = $connectionTypes->select('connection_type', DB::raw('COUNT(*) as count'))
            ->groupBy('connection_type')
            ->pluck('count', 'connection_type')
            ->toArray();

        return response()->json([
            'success' => true,
            'by_status' => $distribution,
            'by_connection_type' => $typeDistribution,
        ]);
    }

    /**
     * Get Monthly Trend Data for Zone
     */
    public function getZoneTrend(Request $request)
    {
        $zoneId = $request->zone_id;
        $months = $request->months ?? 6;

        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();

            $query = Client::query();
            if ($zoneId) {
                $query->where('zone_id', $zoneId);
            }

            $newClients = (clone $query)
                ->whereBetween('connection_date', [$startDate, $endDate])
                ->count();

            $leftClients = (clone $query)
                ->where('status', 'inactive')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count();

            $paymentQuery = Payment::query();
            if ($zoneId) {
                $paymentQuery->whereHas('client', function ($q) use ($zoneId) {
                    $q->where('zone_id', $zoneId);
                });
            }

            $collection = $paymentQuery
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('amount');

            $data[] = [
                'month' => $date->format('M Y'),
                'new_clients' => $newClients,
                'left_clients' => $leftClients,
                'collection' => $collection,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
