<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class AdvancedTicketService
{
    protected array $categoryKeywords = [
        'no_internet' => ['no internet', 'not working', 'offline', 'disconnected', 'no connection'],
        'slow_speed' => ['slow', 'speed', 'buffering', 'lag', 'latency'],
        'los' => ['los', 'light', 'red light', 'fiber cut', 'no light'],
        'billing' => ['bill', 'payment', 'charge', 'invoice', 'due'],
        'router' => ['router', 'wifi', 'wireless', 'signal'],
        'installation' => ['install', 'new connection', 'setup'],
        'upgrade' => ['upgrade', 'package change', 'speed upgrade'],
    ];

    public function autoDetectCategory(string $subject, string $description): string
    {
        $text = strtolower($subject . ' ' . $description);

        foreach ($this->categoryKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    public function getExpectedFixTime(string $category, string $priority): int
    {
        $baseTimes = [
            'no_internet' => 4,
            'slow_speed' => 8,
            'los' => 6,
            'billing' => 24,
            'router' => 4,
            'installation' => 48,
            'upgrade' => 24,
            'general' => 24,
        ];

        $priorityMultipliers = [
            'urgent' => 0.25,
            'high' => 0.5,
            'medium' => 1,
            'low' => 1.5,
        ];

        $baseTime = $baseTimes[$category] ?? 24;
        $multiplier = $priorityMultipliers[$priority] ?? 1;

        return (int) ceil($baseTime * $multiplier);
    }

    public function checkAndEscalate(): array
    {
        $escalated = [];

        $openTickets = Ticket::whereIn('status', ['open', 'in_progress'])
            ->where('created_at', '<', now()->subHours(4))
            ->get();

        foreach ($openTickets as $ticket) {
            $expectedHours = $ticket->expected_fix_hours ?? 24;
            $hoursOpen = $ticket->created_at->diffInHours(now());

            if ($hoursOpen >= $expectedHours && $ticket->escalation_level < 3) {
                $newLevel = $ticket->escalation_level + 1;

                $ticket->update([
                    'escalation_level' => $newLevel,
                    'priority' => $this->upgradePriority($ticket->priority),
                ]);

                DB::table('ticket_escalations')->insert([
                    'ticket_id' => $ticket->id,
                    'reason' => "Auto-escalated: Exceeded expected fix time of {$expectedHours} hours",
                    'escalation_level' => $newLevel,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $escalated[] = [
                    'ticket_id' => $ticket->id,
                    'ticket_no' => $ticket->ticket_no,
                    'new_level' => $newLevel,
                    'hours_open' => $hoursOpen,
                ];
            }
        }

        return $escalated;
    }

    protected function upgradePriority(string $priority): string
    {
        $order = ['low' => 'medium', 'medium' => 'high', 'high' => 'urgent'];
        return $order[$priority] ?? 'urgent';
    }

    public function groupAreaOutageTickets(): array
    {
        $grouped = [];

        $recentTickets = Ticket::where('created_at', '>=', now()->subHours(2))
            ->whereIn('status', ['open', 'in_progress'])
            ->with('client')
            ->get();

        $zoneTickets = [];
        foreach ($recentTickets as $ticket) {
            if ($ticket->client?->zone_id) {
                $zoneId = $ticket->client->zone_id;
                $zoneTickets[$zoneId][] = $ticket;
            }
        }

        foreach ($zoneTickets as $zoneId => $tickets) {
            if (count($tickets) >= 3) {
                $zone = DB::table('zones')->find($zoneId);

                $grouped[] = [
                    'zone_id' => $zoneId,
                    'zone_name' => $zone?->name,
                    'ticket_count' => count($tickets),
                    'ticket_ids' => collect($tickets)->pluck('id')->all(),
                    'likely_outage' => true,
                ];

                $existingOutage = DB::table('area_outage_logs')
                    ->where('zone_id', $zoneId)
                    ->whereNull('resolved_at')
                    ->exists();

                if (!$existingOutage) {
                    DB::table('area_outage_logs')->insert([
                        'zone_id' => $zoneId,
                        'affected_clients' => count($tickets),
                        'outage_type' => 'tickets_spike',
                        'description' => count($tickets) . ' tickets in last 2 hours',
                        'started_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        return $grouped;
    }

    public function getRepeatIssueRootCause(int $clientId): array
    {
        $tickets = Ticket::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        if ($tickets->count() < 3) {
            return ['is_repeat' => false];
        }

        $categories = $tickets->pluck('auto_category')->filter()->countBy();
        $mostCommon = $categories->sortDesc()->keys()->first();

        $hints = [
            'no_internet' => 'Check ONU signal, fiber connection, and router health',
            'slow_speed' => 'Check bandwidth allocation, network congestion, or equipment issues',
            'los' => 'Inspect fiber route for damage or splitter issues',
            'router' => 'Consider router replacement or firmware update',
        ];

        return [
            'is_repeat' => true,
            'ticket_count' => $tickets->count(),
            'most_common_issue' => $mostCommon,
            'root_cause_hint' => $hints[$mostCommon] ?? 'Review client history for patterns',
            'category_breakdown' => $categories->toArray(),
        ];
    }

    public function getClientIssueTimeline(int $clientId): array
    {
        $tickets = Ticket::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return $tickets->map(function ($ticket) {
            return [
                'ticket_id' => $ticket->id,
                'ticket_no' => $ticket->ticket_no,
                'subject' => $ticket->subject,
                'category' => $ticket->auto_category ?? 'general',
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'created_at' => $ticket->created_at,
                'resolved_at' => $ticket->status === 'closed' ? $ticket->updated_at : null,
                'resolution_hours' => $ticket->status === 'closed' 
                    ? $ticket->created_at->diffInHours($ticket->updated_at)
                    : null,
            ];
        })->toArray();
    }

    public function calculateResolutionScore(int $ticketId): array
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return ['error' => 'Ticket not found'];
        }

        $expectedHours = $ticket->expected_fix_hours ?? 24;
        $actualHours = $ticket->created_at->diffInHours($ticket->updated_at ?? now());

        $responseTimeScore = max(0, 100 - (min($actualHours, 48) / 48 * 100));

        $resolutionTimeScore = 100;
        if ($ticket->status === 'closed') {
            $resolutionTimeScore = $actualHours <= $expectedHours 
                ? 100 
                : max(0, 100 - (($actualHours - $expectedHours) / $expectedHours * 50));
        }

        $rating = DB::table('service_ratings')
            ->where('ticket_id', $ticketId)
            ->first();

        $satisfactionScore = $rating ? ($rating->rating / 5) * 100 : 80;

        $reopenCount = DB::table('ticket_escalations')
            ->where('ticket_id', $ticketId)
            ->where('reason', 'like', '%reopen%')
            ->count();

        $fcrScore = $reopenCount === 0 ? 100 : max(0, 100 - ($reopenCount * 30));

        $totalScore = (int) (($responseTimeScore * 0.2) + ($resolutionTimeScore * 0.3) + ($satisfactionScore * 0.3) + ($fcrScore * 0.2));

        DB::table('ticket_resolution_scores')->updateOrInsert(
            ['ticket_id' => $ticketId],
            [
                'response_time_score' => (int) $responseTimeScore,
                'resolution_time_score' => (int) $resolutionTimeScore,
                'customer_satisfaction_score' => (int) $satisfactionScore,
                'first_contact_resolution' => (int) $fcrScore,
                'total_score' => $totalScore,
                'updated_at' => now(),
            ]
        );

        return [
            'ticket_id' => $ticketId,
            'total_score' => $totalScore,
            'breakdown' => [
                'response_time' => (int) $responseTimeScore,
                'resolution_time' => (int) $resolutionTimeScore,
                'satisfaction' => (int) $satisfactionScore,
                'first_contact' => (int) $fcrScore,
            ],
        ];
    }

    public function detectFakeOfflineComplaints(): array
    {
        $alerts = [];

        $offlineTickets = Ticket::whereIn('auto_category', ['no_internet', 'los'])
            ->where('status', 'open')
            ->with('client')
            ->get();

        foreach ($offlineTickets as $ticket) {
            $client = $ticket->client;
            if (!$client) continue;

            $isOnline = DB::table('mikrotik_realtime_sessions')
                ->where('client_id', $client->id)
                ->where('is_active', true)
                ->exists();

            if ($isOnline) {
                $session = DB::table('mikrotik_realtime_sessions')
                    ->where('client_id', $client->id)
                    ->where('is_active', true)
                    ->first();

                $alerts[] = [
                    'ticket_id' => $ticket->id,
                    'ticket_no' => $ticket->ticket_no,
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'complaint' => $ticket->subject,
                    'actual_status' => 'online',
                    'uptime_seconds' => $session?->uptime_seconds,
                    'ip_address' => $session?->ip_address,
                ];
            }
        }

        return $alerts;
    }

    public function broadcastIssueMessage(int $zoneId, string $message): int
    {
        $clients = Client::where('zone_id', $zoneId)
            ->where('status', 'active')
            ->whereNotNull('phone')
            ->get();

        $sent = 0;
        foreach ($clients as $client) {
            DB::table('sms_logs')->insert([
                'phone' => $client->phone,
                'message' => $message,
                'type' => 'broadcast',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sent++;
        }

        return $sent;
    }
}
