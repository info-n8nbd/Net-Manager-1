<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    public function getAdvancedStats(): array
    {
        return [
            'live_online_clients' => $this->getLiveOnlineClients(),
            'auto_inactive_clients' => $this->getAutoInactiveClients(),
            'today_collection' => $this->getTodayCollection(),
            'pending_tickets' => $this->getPendingTickets(),
            'due_bills_aging' => $this->getDueBillsAging(),
            'zone_statistics' => $this->getZoneStatistics(),
            'monthly_collection_vs_discount' => $this->getMonthlyCollectionVsDiscount(),
            'new_clients_last_30_days' => $this->getNewClientsLast30Days(),
            'employee_activity_summary' => $this->getEmployeeActivitySummary(),
            'monthly_signup_fee' => $this->getMonthlySignupFee(),
            'monthly_reseller_collection' => $this->getMonthlyResellerCollection(),
            'running_month_bills' => $this->getRunningMonthBills(),
            'old_due_amount' => $this->getOldDueAmount(),
            'today_expense' => $this->getTodayExpense(),
            'monthly_others_collection' => $this->getMonthlyOthersCollection(),
            'staff_collection_dues' => $this->getStaffCollectionDues(),
        ];
    }
    
    public function getMonthlySignupFee(): float
    {
        return \App\Models\ClientServiceCharge::where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')
            ->where('amount', '>', 0)
            ->whereMonth('charge_date', now()->month)
            ->whereYear('charge_date', now()->year)
            ->sum('amount');
    }
    
    public function getMonthlyResellerCollection(): float
    {
        return Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->whereHas('client', function($q) {
                $q->whereNotNull('reseller_id');
            })
            ->sum('amount');
    }
    
    public function getRunningMonthBills(): float
    {
        return Bill::whereMonth('bill_date', now()->month)
            ->whereYear('bill_date', now()->year)
            ->sum('total_amount');
    }
    
    public function getOldDueAmount(): float
    {
        return Bill::where('status', 'unpaid')
            ->where('due_date', '<', now()->startOfMonth())
            ->sum('total_amount');
    }
    
    public function getTodayExpense(): float
    {
        return DB::table('expenses')
            ->whereDate('expense_date', today())
            ->where('approval_status', 'approved')
            ->sum('amount');
    }
    
    public function getMonthlyOthersCollection(): float
    {
        return Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->whereNull('bill_id')
            ->sum('amount');
    }

    public function getLiveOnlineClients(): int
    {
        return Client::where('connection_status', 'online')->count();
    }

    public function getAutoInactiveClients(): int
    {
        return Client::where('status', 'inactive')
            ->where('expire_date', '<', now())
            ->count();
    }

    public function getTodayCollection(): float
    {
        return Payment::whereDate('payment_date', today())->sum('amount');
    }

    public function getPendingTickets(): int
    {
        return Ticket::whereIn('status', ['open', 'in_progress'])->count();
    }

    public function getDueBillsAging(): array
    {
        $today = now();
        
        return [
            '0_7_days' => Bill::where('status', 'unpaid')
                ->where('due_date', '>=', $today->copy()->subDays(7))
                ->where('due_date', '<=', $today)
                ->count(),
            '7_15_days' => Bill::where('status', 'unpaid')
                ->where('due_date', '>=', $today->copy()->subDays(15))
                ->where('due_date', '<', $today->copy()->subDays(7))
                ->count(),
            '15_plus_days' => Bill::where('status', 'unpaid')
                ->where('due_date', '<', $today->copy()->subDays(15))
                ->count(),
        ];
    }

    public function getZoneStatistics(): array
    {
        return Client::select('zones.name as zone_name', DB::raw('count(*) as total'))
            ->join('zones', 'clients.zone_id', '=', 'zones.id')
            ->groupBy('zones.id', 'zones.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getMonthlyCollectionVsDiscount(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthLabel = $month->format('M Y');
            
            $collection = Payment::whereMonth('payment_date', $month->month)
                ->whereYear('payment_date', $month->year)
                ->sum('amount');
            
            $discount = Bill::whereMonth('bill_date', $month->month)
                ->whereYear('bill_date', $month->year)
                ->sum('discount');
            
            $data[] = [
                'month' => $monthLabel,
                'collection' => $collection,
                'discount' => $discount
            ];
        }
        return $data;
    }

    public function getNewClientsLast30Days(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Client::whereDate('created_at', $date)->count();
            $data[] = [
                'date' => $date->format('d-m'),
                'count' => $count
            ];
        }
        return $data;
    }

    public function getEmployeeActivitySummary(): array
    {
        return ActivityLog::select('users.name', DB::raw('count(*) as activity_count'))
            ->join('users', 'activity_logs.user_id', '=', 'users.id')
            ->whereDate('activity_logs.created_at', today())
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('activity_count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function getIspHealthScore(): array
    {
        $totalClients = Client::where('status', 'active')->count();
        $onlineClients = Client::where('connection_status', 'online')->count();
        $paidClients = Client::whereDoesntHave('bills', function($q) {
            $q->where('status', 'unpaid')->where('due_date', '<', now());
        })->where('status', 'active')->count();
        $resolvedTickets = Ticket::whereMonth('updated_at', now()->month)
            ->where('status', 'closed')->count();
        $totalTickets = Ticket::whereMonth('created_at', now()->month)->count();

        $networkScore = $totalClients > 0 ? ($onlineClients / $totalClients) * 100 : 100;
        $billingScore = $totalClients > 0 ? ($paidClients / $totalClients) * 100 : 100;
        $supportScore = $totalTickets > 0 ? ($resolvedTickets / $totalTickets) * 100 : 100;
        
        $overallScore = ($networkScore * 0.4) + ($billingScore * 0.4) + ($supportScore * 0.2);

        return [
            'overall_score' => round($overallScore, 1),
            'network_score' => round($networkScore, 1),
            'billing_score' => round($billingScore, 1),
            'support_score' => round($supportScore, 1),
            'total_clients' => $totalClients,
            'online_clients' => $onlineClients,
            'paid_clients' => $paidClients,
            'grade' => $this->getHealthGrade($overallScore),
        ];
    }

    protected function getHealthGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    public function getAreaHealthScores(): array
    {
        $areas = DB::table('zones')
            ->select('zones.id', 'zones.name')
            ->get();

        $areaScores = [];
        foreach ($areas as $area) {
            $totalClients = Client::where('zone_id', $area->id)->where('status', 'active')->count();
            if ($totalClients == 0) continue;

            $onlineClients = Client::where('zone_id', $area->id)->where('connection_status', 'online')->count();
            $overdueClients = Client::where('zone_id', $area->id)
                ->whereHas('bills', function($q) {
                    $q->where('status', 'unpaid')->where('due_date', '<', now());
                })->count();

            $networkScore = ($onlineClients / $totalClients) * 100;
            $billingScore = (($totalClients - $overdueClients) / $totalClients) * 100;
            $overallScore = ($networkScore * 0.5) + ($billingScore * 0.5);

            $areaScores[] = [
                'area_id' => $area->id,
                'area_name' => $area->name,
                'total_clients' => $totalClients,
                'online_clients' => $onlineClients,
                'overdue_clients' => $overdueClients,
                'network_score' => round($networkScore, 1),
                'billing_score' => round($billingScore, 1),
                'overall_score' => round($overallScore, 1),
                'grade' => $this->getHealthGrade($overallScore),
            ];
        }

        usort($areaScores, fn($a, $b) => $b['overall_score'] <=> $a['overall_score']);
        return $areaScores;
    }

    public function getChurnRiskClients(): array
    {
        $riskClients = [];
        
        $clients = Client::where('status', 'active')
            ->with(['bills', 'tickets', 'payments'])
            ->get();

        foreach ($clients as $client) {
            $riskScore = 0;
            $riskFactors = [];

            $overdueBills = $client->bills->where('status', 'unpaid')
                ->where('due_date', '<', now())->count();
            if ($overdueBills > 0) {
                $riskScore += min($overdueBills * 15, 30);
                $riskFactors[] = "{$overdueBills} overdue bill(s)";
            }

            $openTickets = $client->tickets->whereIn('status', ['open', 'in_progress'])->count();
            if ($openTickets > 2) {
                $riskScore += 20;
                $riskFactors[] = "{$openTickets} open tickets";
            }

            $lastPayment = $client->payments->sortByDesc('payment_date')->first();
            if ($lastPayment && $lastPayment->payment_date < now()->subDays(45)) {
                $riskScore += 25;
                $riskFactors[] = "No payment in 45+ days";
            }

            if ($client->connection_status === 'offline') {
                $riskScore += 15;
                $riskFactors[] = "Currently offline";
            }

            if ($riskScore >= 30) {
                $riskClients[] = [
                    'client_id' => $client->id,
                    'client_code' => $client->client_id,
                    'name' => $client->name,
                    'risk_score' => min($riskScore, 100),
                    'risk_level' => $riskScore >= 70 ? 'high' : ($riskScore >= 50 ? 'medium' : 'low'),
                    'risk_factors' => $riskFactors,
                ];
            }
        }

        usort($riskClients, fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);
        return array_slice($riskClients, 0, 20);
    }

    public function getRevenueLeakageAlerts(): array
    {
        return DB::table('revenue_leakage_alerts')
            ->join('clients', 'revenue_leakage_alerts.client_id', '=', 'clients.id')
            ->where('revenue_leakage_alerts.is_resolved', false)
            ->select(
                'revenue_leakage_alerts.*',
                'clients.name as client_name',
                'clients.client_id as client_code'
            )
            ->orderByDesc('revenue_leakage_alerts.created_at')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getTechnicianEfficiencyScores(): array
    {
        $technicians = DB::table('users')
            ->leftJoin('model_has_roles', function($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                     ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where(function($q) {
                $q->where('users.user_type', 'technician')
                  ->orWhere('roles.name', 'technician');
            })
            ->select('users.*')
            ->distinct()
            ->get();

        $efficiencyScores = [];
        foreach ($technicians as $tech) {
            $assignedTickets = DB::table('tickets')
                ->where('assigned_to', $tech->id)
                ->whereMonth('created_at', now()->month)
                ->count();

            $resolvedTickets = DB::table('tickets')
                ->where('assigned_to', $tech->id)
                ->where('status', 'closed')
                ->whereMonth('updated_at', now()->month)
                ->count();

            $avgResolutionTime = DB::table('tickets')
                ->where('assigned_to', $tech->id)
                ->where('status', 'closed')
                ->whereMonth('updated_at', now()->month)
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at))/3600) as avg_hours')
                ->value('avg_hours') ?? 0;

            $collections = Payment::where('collected_by', $tech->id)
                ->whereMonth('payment_date', now()->month)
                ->sum('amount');

            $resolutionRate = $assignedTickets > 0 ? ($resolvedTickets / $assignedTickets) * 100 : 0;
            $efficiencyScore = $resolutionRate * 0.6 + (100 - min($avgResolutionTime * 2, 100)) * 0.4;

            $efficiencyScores[] = [
                'technician_id' => $tech->id,
                'name' => $tech->name,
                'assigned_tickets' => $assignedTickets,
                'resolved_tickets' => $resolvedTickets,
                'resolution_rate' => round($resolutionRate, 1),
                'avg_resolution_hours' => round($avgResolutionTime, 1),
                'monthly_collections' => $collections,
                'efficiency_score' => round($efficiencyScore, 1),
            ];
        }

        usort($efficiencyScores, fn($a, $b) => $b['efficiency_score'] <=> $a['efficiency_score']);
        return $efficiencyScores;
    }

    public function getNetworkEventsSummary(): array
    {
        return [
            'today_events' => DB::table('network_event_logs')
                ->whereDate('created_at', today())
                ->count(),
            'router_down_alerts' => DB::table('network_event_logs')
                ->where('event_type', 'router_down')
                ->where('is_resolved', false)
                ->count(),
            'fiber_cut_alerts' => DB::table('network_event_logs')
                ->where('event_type', 'fiber_cut')
                ->where('is_resolved', false)
                ->count(),
            'recent_events' => DB::table('network_event_logs')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    public function getSmartDiscountSuggestions(): array
    {
        $suggestions = [];

        $loyalClients = Client::where('status', 'active')
            ->where('created_at', '<', now()->subYears(2))
            ->whereDoesntHave('bills', function($q) {
                $q->where('status', 'unpaid');
            })
            ->limit(10)
            ->get();

        foreach ($loyalClients as $client) {
            $currentDiscount = $client->permanent_discount ?? 0;
            if ($currentDiscount < ($client->monthly_fee * 0.1)) {
                $suggestions[] = [
                    'client_id' => $client->id,
                    'client_code' => $client->client_id,
                    'name' => $client->name,
                    'reason' => 'Loyal customer (2+ years, no overdue)',
                    'suggested_discount' => round($client->monthly_fee * 0.05, 0),
                    'current_discount' => $currentDiscount,
                ];
            }
        }

        return $suggestions;
    }
    
    public function getStaffCollectionDues(): array
    {
        $staffDues = [];
        
        try {
            $collectors = Payment::select('collected_by')
                ->whereNotNull('collected_by')
                ->distinct()
                ->pluck('collected_by');
            
            $hasReceiptsTable = \Illuminate\Support\Facades\Schema::hasTable('staff_receipts');
            
            foreach ($collectors as $collectorId) {
                $user = \App\Models\User::find($collectorId);
                if (!$user) continue;
                
                $totalCollected = Payment::where('collected_by', $collectorId)
                    ->sum('amount');
                
                $receipted = 0;
                if ($hasReceiptsTable) {
                    $receipted = DB::table('staff_receipts')
                        ->where('staff_id', $collectorId)
                        ->sum('amount');
                }
                
                $currentDue = $totalCollected - $receipted;
                
                if ($currentDue > 0) {
                    $staffDues[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'user_type' => $user->user_type ?? 'staff',
                        'total_collected' => $totalCollected,
                        'receipted' => $receipted,
                        'current_due' => $currentDue,
                    ];
                }
            }
            
            usort($staffDues, function($a, $b) {
                return $b['current_due'] <=> $a['current_due'];
            });
        } catch (\Exception $e) {
            return [];
        }
        
        return array_slice($staffDues, 0, 10);
    }
}
