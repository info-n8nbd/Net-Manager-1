<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ResellerService
{
    public function getResellerPerformanceScore(int $resellerId): array
    {
        $reseller = User::find($resellerId);
        if (!$reseller) {
            return ['error' => 'Reseller not found'];
        }

        $totalClients = Client::where('reseller_id', $resellerId)->count();
        $activeClients = Client::where('reseller_id', $resellerId)->where('status', 'active')->count();

        $newClients = Client::where('reseller_id', $resellerId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $monthlyCollection = Payment::whereHas('client', fn($q) => $q->where('reseller_id', $resellerId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $expectedRevenue = Client::where('reseller_id', $resellerId)
            ->where('status', 'active')
            ->sum('monthly_fee');

        $collectionRate = $expectedRevenue > 0 ? ($monthlyCollection / $expectedRevenue) * 100 : 0;

        $churnedClients = Client::where('reseller_id', $resellerId)
            ->where('status', 'inactive')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        $churnRate = $totalClients > 0 ? ($churnedClients / $totalClients) * 100 : 0;

        $clientGrowthScore = min(100, $newClients * 10);
        $retentionScore = max(0, 100 - ($churnRate * 5));
        $collectionScore = min(100, $collectionRate);
        $clientBaseScore = min(100, ($activeClients / 50) * 100);

        $overallScore = ($clientGrowthScore * 0.25) + ($retentionScore * 0.25) + ($collectionScore * 0.3) + ($clientBaseScore * 0.2);

        return [
            'reseller_id' => $resellerId,
            'name' => $reseller->name,
            'overall_score' => round($overallScore),
            'metrics' => [
                'total_clients' => $totalClients,
                'active_clients' => $activeClients,
                'new_clients_30d' => $newClients,
                'monthly_collection' => $monthlyCollection,
                'collection_rate' => round($collectionRate, 1),
                'churn_rate' => round($churnRate, 1),
            ],
            'score_breakdown' => [
                'client_growth' => round($clientGrowthScore),
                'retention' => round($retentionScore),
                'collection' => round($collectionScore),
                'client_base' => round($clientBaseScore),
            ],
        ];
    }

    public function calculateCommission(int $resellerId, string $month): array
    {
        $existingCommission = DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->where('month', $month)
            ->first();

        if ($existingCommission && $existingCommission->is_settled) {
            return ['error' => 'Commission already settled'];
        }

        [$year, $monthNum] = explode('-', $month);

        $totalClients = Client::where('reseller_id', $resellerId)
            ->where('status', 'active')
            ->count();

        $newClients = Client::where('reseller_id', $resellerId)
            ->whereMonth('created_at', $monthNum)
            ->whereYear('created_at', $year)
            ->count();

        $collection = Payment::whereHas('client', fn($q) => $q->where('reseller_id', $resellerId))
            ->whereMonth('created_at', $monthNum)
            ->whereYear('created_at', $year)
            ->sum('amount');

        $commissionRate = $this->getCommissionRate($totalClients);
        $commissionAmount = $collection * ($commissionRate / 100);

        $newClientBonus = $newClients * 50;

        $totalCommission = $commissionAmount + $newClientBonus;

        DB::table('reseller_commissions')->updateOrInsert(
            ['reseller_id' => $resellerId, 'month' => $month],
            [
                'total_clients' => $totalClients,
                'new_clients' => $newClients,
                'collection_amount' => $collection,
                'commission_rate' => $commissionRate,
                'commission_amount' => $totalCommission,
                'updated_at' => now(),
            ]
        );

        return [
            'reseller_id' => $resellerId,
            'month' => $month,
            'collection' => $collection,
            'commission_rate' => $commissionRate,
            'base_commission' => round($commissionAmount, 2),
            'new_client_bonus' => $newClientBonus,
            'total_commission' => round($totalCommission, 2),
        ];
    }

    protected function getCommissionRate(int $clientCount): float
    {
        if ($clientCount >= 100) return 10;
        if ($clientCount >= 50) return 8;
        if ($clientCount >= 25) return 6;
        return 5;
    }

    public function settleCommission(int $resellerId, string $month): array
    {
        $commission = DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->where('month', $month)
            ->first();

        if (!$commission) {
            return ['error' => 'Commission not found'];
        }

        if ($commission->is_settled) {
            return ['error' => 'Already settled'];
        }

        DB::table('reseller_commissions')
            ->where('id', $commission->id)
            ->update([
                'is_settled' => true,
                'settled_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('users')
            ->where('id', $resellerId)
            ->increment('wallet_balance', $commission->commission_amount);

        return [
            'success' => true,
            'amount' => $commission->commission_amount,
            'settled_at' => now()->toIso8601String(),
        ];
    }

    public function getResellerBoundaries(int $resellerId): array
    {
        $boundaries = DB::table('reseller_boundaries')
            ->join('zones', 'reseller_boundaries.zone_id', '=', 'zones.id')
            ->where('reseller_boundaries.reseller_id', $resellerId)
            ->select('zones.*', 'reseller_boundaries.is_exclusive')
            ->get();

        return $boundaries->map(fn($b) => [
            'zone_id' => $b->id,
            'zone_name' => $b->name,
            'is_exclusive' => (bool) $b->is_exclusive,
        ])->toArray();
    }

    public function setResellerBoundary(int $resellerId, int $zoneId, bool $isExclusive = false): array
    {
        if ($isExclusive) {
            $existing = DB::table('reseller_boundaries')
                ->where('zone_id', $zoneId)
                ->where('is_exclusive', true)
                ->where('reseller_id', '!=', $resellerId)
                ->exists();

            if ($existing) {
                return ['error' => 'Zone is already exclusively assigned'];
            }
        }

        DB::table('reseller_boundaries')->updateOrInsert(
            ['reseller_id' => $resellerId, 'zone_id' => $zoneId],
            [
                'is_exclusive' => $isExclusive,
                'updated_at' => now(),
            ]
        );

        return ['success' => true];
    }

    public function getResellerChurnAlert(): array
    {
        $alerts = [];

        $resellers = User::role('reseller')->get();

        foreach ($resellers as $reseller) {
            $totalClients = Client::where('reseller_id', $reseller->id)->count();
            
            $churnedClients = Client::where('reseller_id', $reseller->id)
                ->where('status', 'inactive')
                ->where('updated_at', '>=', now()->subDays(30))
                ->count();

            $atRiskClients = Client::where('reseller_id', $reseller->id)
                ->where('status', 'active')
                ->where('churn_risk_score', '>', 50)
                ->count();

            $churnRate = $totalClients > 0 ? ($churnedClients / $totalClients) * 100 : 0;

            if ($churnRate > 5 || $atRiskClients > 3) {
                $alerts[] = [
                    'reseller_id' => $reseller->id,
                    'reseller_name' => $reseller->name,
                    'total_clients' => $totalClients,
                    'churned_30d' => $churnedClients,
                    'churn_rate' => round($churnRate, 1),
                    'at_risk_clients' => $atRiskClients,
                    'severity' => $churnRate > 10 ? 'critical' : 'warning',
                ];
            }
        }

        return $alerts;
    }

    public function getCustomPackages(int $resellerId): array
    {
        return DB::table('reseller_packages')
            ->join('packages', 'reseller_packages.package_id', '=', 'packages.id')
            ->where('reseller_packages.reseller_id', $resellerId)
            ->where('reseller_packages.is_available', true)
            ->select('packages.*', 'reseller_packages.custom_price')
            ->get()
            ->map(fn($p) => [
                'package_id' => $p->id,
                'name' => $p->name,
                'speed' => $p->speed,
                'original_price' => $p->price,
                'custom_price' => $p->custom_price ?? $p->price,
            ])
            ->toArray();
    }

    public function getResellerGrowthGraph(int $resellerId): array
    {
        $months = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('Y-m');
            
            $clientCount = Client::where('reseller_id', $resellerId)
                ->where('created_at', '<=', $date->endOfMonth())
                ->count();

            $revenue = Payment::whereHas('client', fn($q) => $q->where('reseller_id', $resellerId))
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');

            $months[] = [
                'month' => $month,
                'label' => $date->format('M Y'),
                'clients' => $clientCount,
                'revenue' => $revenue,
            ];
        }

        return $months;
    }

    public function getDueRiskAlert(): array
    {
        $alerts = [];

        $resellers = User::role('reseller')->get();

        foreach ($resellers as $reseller) {
            $overdueBills = DB::table('bills')
                ->join('clients', 'bills.client_id', '=', 'clients.id')
                ->where('clients.reseller_id', $reseller->id)
                ->where('bills.status', 'unpaid')
                ->where('bills.due_date', '<', now())
                ->sum('bills.total_amount');

            $expectedRevenue = Client::where('reseller_id', $reseller->id)
                ->where('status', 'active')
                ->sum('monthly_fee');

            $overduePercent = $expectedRevenue > 0 ? ($overdueBills / $expectedRevenue) * 100 : 0;

            if ($overduePercent > 20) {
                $alerts[] = [
                    'reseller_id' => $reseller->id,
                    'reseller_name' => $reseller->name,
                    'overdue_amount' => $overdueBills,
                    'expected_revenue' => $expectedRevenue,
                    'overdue_percent' => round($overduePercent, 1),
                    'severity' => $overduePercent > 40 ? 'critical' : 'warning',
                ];
            }
        }

        return $alerts;
    }
}
