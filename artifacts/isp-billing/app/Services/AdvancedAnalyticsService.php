<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class AdvancedAnalyticsService
{
    public function calculateClientLifetimeValue(int $clientId): array
    {
        $client = Client::find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $totalRevenue = DB::table('payments')
            ->where('client_id', $clientId)
            ->sum('amount');

        $tenureMonths = $client->created_at->diffInMonths(now()) ?: 1;

        $avgMonthlyRevenue = $totalRevenue / $tenureMonths;

        $churnRisk = $client->churn_risk_score ?? 10;
        $expectedLifetimeMonths = max(1, (100 - $churnRisk) / 5 * 12);

        $predictedClv = $avgMonthlyRevenue * $expectedLifetimeMonths;

        $segment = $this->getClientSegment($predictedClv);

        DB::table('client_lifetime_values')->updateOrInsert(
            ['client_id' => $clientId],
            [
                'total_revenue' => $totalRevenue,
                'avg_monthly_revenue' => $avgMonthlyRevenue,
                'tenure_months' => $tenureMonths,
                'predicted_clv' => $predictedClv,
                'segment' => $segment,
                'updated_at' => now(),
            ]
        );

        $client->update(['lifetime_value' => $predictedClv]);

        return [
            'client_id' => $clientId,
            'total_revenue' => round($totalRevenue, 2),
            'avg_monthly_revenue' => round($avgMonthlyRevenue, 2),
            'tenure_months' => $tenureMonths,
            'predicted_clv' => round($predictedClv, 2),
            'segment' => $segment,
        ];
    }

    protected function getClientSegment(float $clv): string
    {
        if ($clv >= 50000) return 'platinum';
        if ($clv >= 25000) return 'gold';
        if ($clv >= 10000) return 'silver';
        return 'bronze';
    }

    public function predictChurn(int $clientId): array
    {
        $client = Client::find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $riskFactors = [];
        $riskScore = 0;

        $lastPayment = DB::table('payments')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->first();

        if ($lastPayment) {
            $daysSincePayment = now()->diffInDays($lastPayment->created_at);
            if ($daysSincePayment > 45) {
                $riskScore += 25;
                $riskFactors[] = 'No payment in 45+ days';
            }
        } else {
            $riskScore += 30;
            $riskFactors[] = 'No payment history';
        }

        $recentTickets = Ticket::where('client_id', $clientId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($recentTickets >= 3) {
            $riskScore += 20;
            $riskFactors[] = 'Multiple support tickets recently';
        }

        $overdueBills = DB::table('bills')
            ->where('client_id', $clientId)
            ->where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->count();

        if ($overdueBills > 0) {
            $riskScore += 15 * min($overdueBills, 3);
            $riskFactors[] = "{$overdueBills} overdue bill(s)";
        }

        if ($client->connection_status === 'offline') {
            $offlineDays = $client->last_online 
                ? now()->diffInDays($client->last_online) 
                : 30;
            if ($offlineDays > 7) {
                $riskScore += 20;
                $riskFactors[] = "Offline for {$offlineDays}+ days";
            }
        }

        $speedTests = DB::table('speed_test_logs')
            ->where('client_id', $clientId)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        if ($speedTests->count() > 0) {
            $avgSpeedPercent = $speedTests->avg('download_speed') / ($client->package?->speed ?? 10) * 100;
            if ($avgSpeedPercent < 70) {
                $riskScore += 15;
                $riskFactors[] = 'Consistently slow speeds';
            }
        }

        $riskScore = min(100, $riskScore);

        $recommendedAction = $this->getRetentionAction($riskScore, $riskFactors);

        DB::table('churn_predictions')->updateOrInsert(
            ['client_id' => $clientId],
            [
                'churn_probability' => $riskScore,
                'risk_factors' => json_encode($riskFactors),
                'recommended_action' => $recommendedAction,
                'updated_at' => now(),
            ]
        );

        $client->update(['churn_risk_score' => $riskScore]);

        return [
            'client_id' => $clientId,
            'churn_probability' => $riskScore,
            'risk_level' => $this->getRiskLevel($riskScore),
            'risk_factors' => $riskFactors,
            'recommended_action' => $recommendedAction,
        ];
    }

    protected function getRiskLevel(float $score): string
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }

    protected function getRetentionAction(float $score, array $factors): string
    {
        if ($score >= 70) {
            return 'Urgent: Personal call from manager, offer special retention discount';
        }
        if ($score >= 40) {
            if (in_array('Multiple support tickets recently', $factors)) {
                return 'Send technician for proactive checkup';
            }
            return 'Send personalized retention offer via SMS';
        }
        return 'Standard engagement - send loyalty appreciation message';
    }

    public function calculateInternetQualityScore(int $clientId): array
    {
        $client = Client::find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $speedTests = DB::table('speed_test_logs')
            ->where('client_id', $clientId)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        $packageSpeed = $client->package?->speed ?? 10;

        $avgSpeed = $speedTests->count() > 0 ? $speedTests->avg('download_speed') : $packageSpeed;
        $avgSpeedPercent = min(100, ($avgSpeed / $packageSpeed) * 100);

        $uptime = DB::table('uptime_logs')
            ->where('client_id', $clientId)
            ->where('log_date', '>=', now()->subDays(7))
            ->avg('uptime_percent') ?? 100;

        $avgPing = $speedTests->count() > 0 ? $speedTests->avg('ping_ms') : 10;
        $latencyScore = max(0, 100 - ($avgPing / 2));

        $totalScore = (int) (($avgSpeedPercent * 0.4) + ($uptime * 0.4) + ($latencyScore * 0.2));

        DB::table('internet_quality_scores')->updateOrInsert(
            ['client_id' => $clientId, 'score_date' => today()],
            [
                'quality_score' => $totalScore,
                'avg_speed_percent' => $avgSpeedPercent,
                'uptime_percent' => $uptime,
                'latency_score' => (int) $latencyScore,
                'packet_loss_score' => 100,
                'updated_at' => now(),
            ]
        );

        $client->update(['internet_quality_score' => $totalScore]);

        return [
            'client_id' => $clientId,
            'quality_score' => $totalScore,
            'grade' => $this->getQualityGrade($totalScore),
            'breakdown' => [
                'speed_percent' => round($avgSpeedPercent, 1),
                'uptime_percent' => round($uptime, 1),
                'latency_score' => round($latencyScore, 1),
            ],
        ];
    }

    protected function getQualityGrade(int $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    public function getAreaProfitabilityRanking(): array
    {
        $zones = DB::table('zones')->get();
        $rankings = [];

        foreach ($zones as $zone) {
            $totalClients = Client::where('zone_id', $zone->id)->count();
            
            $revenue = DB::table('payments')
                ->join('clients', 'payments.client_id', '=', 'clients.id')
                ->where('clients.zone_id', $zone->id)
                ->whereMonth('payments.created_at', now()->month)
                ->whereYear('payments.created_at', now()->year)
                ->sum('payments.amount');

            $expectedRevenue = Client::where('zone_id', $zone->id)
                ->where('status', 'active')
                ->sum('monthly_fee');

            $collectionRate = $expectedRevenue > 0 ? ($revenue / $expectedRevenue) * 100 : 0;

            $operatingCost = $totalClients * 50;

            $profit = $revenue - $operatingCost;

            $rankings[] = [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'total_clients' => $totalClients,
                'revenue' => $revenue,
                'collection_rate' => round($collectionRate, 1),
                'operating_cost' => $operatingCost,
                'profit' => $profit,
            ];
        }

        usort($rankings, fn($a, $b) => $b['profit'] <=> $a['profit']);

        foreach ($rankings as $i => &$ranking) {
            $ranking['rank'] = $i + 1;
        }

        return $rankings;
    }

    public function getHighComplaintAreas(): array
    {
        $alerts = [];

        $zones = DB::table('zones')->get();

        foreach ($zones as $zone) {
            $clientCount = Client::where('zone_id', $zone->id)->count();
            if ($clientCount < 5) continue;

            $ticketCount = Ticket::whereHas('client', fn($q) => $q->where('zone_id', $zone->id))
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            $complaintRate = ($ticketCount / $clientCount) * 100;

            if ($complaintRate > 20) {
                $alerts[] = [
                    'zone_id' => $zone->id,
                    'zone_name' => $zone->name,
                    'client_count' => $clientCount,
                    'ticket_count' => $ticketCount,
                    'complaint_rate' => round($complaintRate, 1),
                    'severity' => $complaintRate > 40 ? 'critical' : 'warning',
                ];
            }
        }

        usort($alerts, fn($a, $b) => $b['complaint_rate'] <=> $a['complaint_rate']);

        return $alerts;
    }

    public function getMonthlyChurnPredictionList(): array
    {
        $atRiskClients = Client::where('status', 'active')
            ->where('churn_risk_score', '>', 40)
            ->orderByDesc('churn_risk_score')
            ->limit(50)
            ->get();

        return $atRiskClients->map(function ($client) {
            $prediction = DB::table('churn_predictions')
                ->where('client_id', $client->id)
                ->first();

            return [
                'client_id' => $client->id,
                'client_code' => $client->client_id,
                'name' => $client->name,
                'churn_probability' => $client->churn_risk_score,
                'risk_factors' => $prediction ? json_decode($prediction->risk_factors, true) : [],
                'recommended_action' => $prediction?->recommended_action,
                'monthly_fee' => $client->monthly_fee,
                'lifetime_value' => $client->lifetime_value,
            ];
        })->toArray();
    }

    public function getClientDowngradeUpgradeTrend(): array
    {
        $upgrades = DB::table('client_timeline')
            ->where('event_type', 'package_change')
            ->where('title', 'like', '%upgrade%')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $downgrades = DB::table('client_timeline')
            ->where('event_type', 'package_change')
            ->where('title', 'like', '%downgrade%')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'period' => '30 days',
            'upgrades' => $upgrades,
            'downgrades' => $downgrades,
            'net_movement' => $upgrades - $downgrades,
            'trend' => $upgrades > $downgrades ? 'positive' : ($upgrades < $downgrades ? 'negative' : 'neutral'),
        ];
    }

    public function getOneClickRetentionActions(int $clientId): array
    {
        $client = Client::with('package')->find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $prediction = DB::table('churn_predictions')
            ->where('client_id', $clientId)
            ->first();

        $actions = [];

        $actions[] = [
            'action' => 'apply_discount',
            'label' => 'Apply 10% Retention Discount',
            'params' => ['discount_percent' => 10],
        ];

        $actions[] = [
            'action' => 'speed_boost',
            'label' => 'Offer Free Speed Upgrade for 1 Month',
            'params' => ['duration_days' => 30],
        ];

        $actions[] = [
            'action' => 'schedule_call',
            'label' => 'Schedule Personal Call',
            'params' => ['type' => 'retention_call'],
        ];

        $actions[] = [
            'action' => 'send_offer',
            'label' => 'Send Loyalty Offer SMS',
            'params' => ['template' => 'loyalty_offer'],
        ];

        if ($client->connection_status === 'offline') {
            array_unshift($actions, [
                'action' => 'create_priority_ticket',
                'label' => 'Create Priority Support Ticket',
                'params' => ['priority' => 'urgent'],
            ]);
        }

        return [
            'client_id' => $clientId,
            'churn_risk' => $client->churn_risk_score,
            'actions' => $actions,
        ];
    }
}
