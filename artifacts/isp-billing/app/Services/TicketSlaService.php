<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketSlaService
{
    protected array $slaConfig = [
        'response' => [
            'urgent' => 30,
            'high' => 60,
            'medium' => 120,
            'low' => 240,
        ],
        'resolution' => [
            'urgent' => 120,
            'high' => 240,
            'medium' => 480,
            'low' => 1440,
        ],
    ];

    protected RealTimeAlertService $alertService;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->alertService = app(RealTimeAlertService::class);
        $this->notificationService = app(NotificationService::class);
    }

    public function initializeSla(Ticket $ticket): void
    {
        $priority = $ticket->priority ?? 'medium';
        $responseMinutes = $this->slaConfig['response'][$priority] ?? 120;
        $resolutionMinutes = $this->slaConfig['resolution'][$priority] ?? 480;

        $ticket->update([
            'sla_response_due_at' => now()->addMinutes($responseMinutes),
            'sla_resolution_due_at' => now()->addMinutes($resolutionMinutes),
        ]);

        DB::table('ticket_sla_timers')->insert([
            ['ticket_id' => $ticket->id, 'sla_type' => 'response', 'target_minutes' => $responseMinutes, 'started_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['ticket_id' => $ticket->id, 'sla_type' => 'resolution', 'target_minutes' => $resolutionMinutes, 'started_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function recordFirstResponse(Ticket $ticket): void
    {
        if ($ticket->first_response_at) {
            return;
        }

        $ticket->update(['first_response_at' => now()]);

        $slaTimer = DB::table('ticket_sla_timers')
            ->where('ticket_id', $ticket->id)
            ->where('sla_type', 'response')
            ->first();

        if ($slaTimer) {
            $breached = now()->gt($ticket->sla_response_due_at);
            
            DB::table('ticket_sla_timers')
                ->where('id', $slaTimer->id)
                ->update([
                    'completed_at' => now(),
                    'breached' => $breached,
                    'breached_at' => $breached ? $ticket->sla_response_due_at : null,
                    'updated_at' => now(),
                ]);

            if ($breached) {
                $ticket->update(['sla_response_breached' => true]);
            }
        }
    }

    public function recordResolution(Ticket $ticket): void
    {
        $slaTimer = DB::table('ticket_sla_timers')
            ->where('ticket_id', $ticket->id)
            ->where('sla_type', 'resolution')
            ->first();

        if ($slaTimer) {
            $breached = now()->gt($ticket->sla_resolution_due_at);
            
            DB::table('ticket_sla_timers')
                ->where('id', $slaTimer->id)
                ->update([
                    'completed_at' => now(),
                    'breached' => $breached,
                    'breached_at' => $breached ? $ticket->sla_resolution_due_at : null,
                    'updated_at' => now(),
                ]);

            if ($breached) {
                $ticket->update(['sla_resolution_breached' => true]);
            }
        }
    }

    public function checkAndEscalate(): array
    {
        $escalated = [];

        $overdueTickets = Ticket::whereIn('status', ['open', 'in_progress'])
            ->where(function($q) {
                $q->where(function($q2) {
                    $q2->whereNull('first_response_at')
                       ->where('sla_response_due_at', '<', now());
                })->orWhere(function($q2) {
                    $q2->where('sla_resolution_due_at', '<', now());
                });
            })
            ->get();

        foreach ($overdueTickets as $ticket) {
            $newLevel = $ticket->escalation_level + 1;
            
            if ($newLevel <= 3) {
                $ticket->update([
                    'escalation_level' => $newLevel,
                    'priority' => $this->upgradePriority($ticket->priority),
                ]);

                $this->notifyEscalation($ticket, $newLevel);
                $escalated[] = $ticket->id;
            }
        }

        return $escalated;
    }

    protected function upgradePriority(?string $current): string
    {
        $priorities = ['low' => 'medium', 'medium' => 'high', 'high' => 'urgent'];
        return $priorities[$current ?? 'medium'] ?? 'urgent';
    }

    protected function notifyEscalation(Ticket $ticket, int $level): void
    {
        $this->alertService->createAlert([
            'alert_type' => 'ticket_escalated',
            'severity' => 'warning',
            'title' => "Ticket #{$ticket->id} Escalated to Level {$level}",
            'message' => "SLA breach detected for ticket: {$ticket->subject}",
            'client_id' => $ticket->client_id,
            'target_role' => 'admin',
            'metadata' => [
                'ticket_id' => $ticket->id,
                'escalation_level' => $level,
                'new_priority' => $ticket->priority,
            ],
        ]);
    }

    public function getSlaStatus(Ticket $ticket): array
    {
        $now = now();
        
        $responseStatus = 'pending';
        $responseRemaining = null;
        if ($ticket->first_response_at) {
            $responseStatus = $ticket->sla_response_breached ? 'breached' : 'met';
        } elseif ($ticket->sla_response_due_at) {
            $responseRemaining = $now->diffInMinutes($ticket->sla_response_due_at, false);
            $responseStatus = $responseRemaining < 0 ? 'breached' : 'active';
        }

        $resolutionStatus = 'pending';
        $resolutionRemaining = null;
        if ($ticket->status === 'resolved' || $ticket->status === 'closed') {
            $resolutionStatus = $ticket->sla_resolution_breached ? 'breached' : 'met';
        } elseif ($ticket->sla_resolution_due_at) {
            $resolutionRemaining = $now->diffInMinutes($ticket->sla_resolution_due_at, false);
            $resolutionStatus = $resolutionRemaining < 0 ? 'breached' : 'active';
        }

        return [
            'response' => [
                'status' => $responseStatus,
                'remaining_minutes' => $responseRemaining,
                'due_at' => $ticket->sla_response_due_at,
                'completed_at' => $ticket->first_response_at,
            ],
            'resolution' => [
                'status' => $resolutionStatus,
                'remaining_minutes' => $resolutionRemaining,
                'due_at' => $ticket->sla_resolution_due_at,
            ],
            'escalation_level' => $ticket->escalation_level,
        ];
    }

    public function assignTechnician(Ticket $ticket, int $technicianId): void
    {
        $ticket->update(['assigned_to' => $technicianId]);
        
        $technician = DB::table('users')->find($technicianId);
        
        $this->alertService->createTechnicianJobAlert(
            $technicianId,
            $ticket->id,
            "New ticket assigned: {$ticket->subject}"
        );

        $client = $ticket->client;
        if ($client) {
            $this->notificationService->notifyClient($client, 'ticket_assigned', [
                'ticket_id' => $ticket->id,
                'technician_name' => $technician->name ?? 'Technician',
            ]);
        }
    }

    public function autoAssignTechnician(Ticket $ticket): ?int
    {
        $client = $ticket->client;
        if (!$client || !$client->zone_id) {
            return null;
        }

        $technician = DB::table('users')
            ->join('user_zones', 'users.id', '=', 'user_zones.user_id')
            ->leftJoin('model_has_roles', function($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                     ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('user_zones.zone_id', $client->zone_id)
            ->where(function($q) {
                $q->where('users.user_type', 'technician')
                  ->orWhere('roles.name', 'technician');
            })
            ->where('users.is_active', true)
            ->select('users.id', DB::raw('(SELECT COUNT(*) FROM tickets WHERE assigned_to = users.id AND status IN (\'open\', \'in_progress\')) as pending_count'))
            ->orderBy('pending_count')
            ->first();

        if ($technician) {
            $this->assignTechnician($ticket, $technician->id);
            return $technician->id;
        }

        return null;
    }

    public function getSlaSummary(): array
    {
        $tickets = Ticket::whereIn('status', ['open', 'in_progress', 'resolved'])
            ->where('created_at', '>', now()->subDays(30))
            ->get();

        $total = $tickets->count();
        $responseBreached = $tickets->where('sla_response_breached', true)->count();
        $resolutionBreached = $tickets->where('sla_resolution_breached', true)->count();

        return [
            'total_tickets' => $total,
            'response_sla_met' => $total > 0 ? round((($total - $responseBreached) / $total) * 100, 1) : 100,
            'resolution_sla_met' => $total > 0 ? round((($total - $resolutionBreached) / $total) * 100, 1) : 100,
            'response_breached' => $responseBreached,
            'resolution_breached' => $resolutionBreached,
        ];
    }
}
