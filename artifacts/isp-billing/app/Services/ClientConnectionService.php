<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientConnectionService
{
    protected MikroTikRealtimeService $mikrotikService;
    protected RealTimeAlertService $alertService;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->mikrotikService = app(MikroTikRealtimeService::class);
        $this->alertService = app(RealTimeAlertService::class);
        $this->notificationService = app(NotificationService::class);
    }

    public function autoDisableOverdueClient(Client $client): bool
    {
        if ($client->status === 'inactive') {
            return false;
        }

        try {
            $result = $this->mikrotikService->disconnectClient($client->id, 'Payment overdue - auto disabled');
            
            $previousStatus = $client->status;
            $client->update([
                'status' => 'inactive',
                'connection_status' => 'offline',
            ]);

            $this->logReconnectAction($client->id, 'disable', 'auto_overdue', 'success', 
                "Auto-disabled due to overdue payment. Due: ৳{$client->due_amount}", 
                $previousStatus, 'inactive');

            $this->notificationService->notifyClient($client, 'connection_disabled', [
                'reason' => 'Payment overdue',
                'due_amount' => $client->due_amount,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logReconnectAction($client->id, 'disable', 'auto_overdue', 'failed', $e->getMessage());
            Log::error("Auto disable failed for client {$client->id}: " . $e->getMessage());
            return false;
        }
    }

    public function autoEnableAfterPayment(Client $client): bool
    {
        if ($client->due_amount > 0) {
            return false;
        }

        try {
            $previousStatus = $client->status;
            $client->update([
                'status' => 'active',
            ]);

            $mikrotik = $client->mikrotikDevice;
            if ($mikrotik) {
                $this->mikrotikService->enableClient($client);
            }

            $this->logReconnectAction($client->id, 'enable', 'payment_cleared', 'success',
                'Auto-enabled after full payment received', $previousStatus, 'active');

            $this->notificationService->notifyClient($client, 'connection_enabled', [
                'reason' => 'Payment received',
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logReconnectAction($client->id, 'enable', 'payment_cleared', 'failed', $e->getMessage());
            Log::error("Auto enable failed for client {$client->id}: " . $e->getMessage());
            return false;
        }
    }

    public function manualDisable(Client $client, int $userId, string $reason = ''): bool
    {
        try {
            $previousStatus = $client->status;
            $client->update([
                'status' => 'inactive',
                'connection_status' => 'offline',
            ]);

            $this->mikrotikService->disconnectClient($client->id, $reason ?: 'Manual disable');

            $this->logReconnectAction($client->id, 'disable', 'manual', 'success', 
                "Manually disabled by user {$userId}. Reason: {$reason}", 
                $previousStatus, 'inactive');

            return true;
        } catch (\Exception $e) {
            Log::error("Manual disable failed: " . $e->getMessage());
            return false;
        }
    }

    public function manualEnable(Client $client, int $userId, string $reason = ''): bool
    {
        try {
            $previousStatus = $client->status;
            $client->update(['status' => 'active']);

            $mikrotik = $client->mikrotikDevice;
            if ($mikrotik) {
                $this->mikrotikService->enableClient($client);
            }

            $this->logReconnectAction($client->id, 'enable', 'manual', 'success',
                "Manually enabled by user {$userId}. Reason: {$reason}",
                $previousStatus, 'active');

            return true;
        } catch (\Exception $e) {
            Log::error("Manual enable failed: " . $e->getMessage());
            return false;
        }
    }

    protected function logReconnectAction(int $clientId, string $action, string $trigger, string $result, 
                                          string $details = '', ?string $previousStatus = null, ?string $newStatus = null): void
    {
        DB::table('client_reconnect_logs')->insert([
            'client_id' => $clientId,
            'action' => $action,
            'trigger' => $trigger,
            'result' => $result,
            'details' => $details,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getReconnectHistory(int $clientId, int $limit = 20): array
    {
        return DB::table('client_reconnect_logs')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getClientLiveStatus(Client $client): array
    {
        $isOnline = $client->connection_status === 'online';
        $dueDate = $client->due_date ? \Carbon\Carbon::parse($client->due_date) : null;
        $daysRemaining = $dueDate ? now()->diffInDays($dueDate, false) : null;

        return [
            'is_online' => $isOnline,
            'connection_status' => $client->connection_status,
            'status' => $client->status,
            'last_seen' => $client->last_seen_at,
            'session_uptime' => $client->session_uptime_seconds,
            'session_uptime_formatted' => $this->formatUptime($client->session_uptime_seconds),
            'current_speed' => [
                'rx' => $client->current_rx_speed,
                'tx' => $client->current_tx_speed,
                'rx_formatted' => $this->formatSpeed($client->current_rx_speed),
                'tx_formatted' => $this->formatSpeed($client->current_tx_speed),
            ],
            'usage' => [
                'today' => $client->today_usage_bytes,
                'today_formatted' => $this->formatBytes($client->today_usage_bytes),
                'month' => $client->month_usage_bytes,
                'month_formatted' => $this->formatBytes($client->month_usage_bytes),
            ],
            'due' => [
                'amount' => $client->due_amount,
                'amount_formatted' => '৳' . number_format($client->due_amount, 0),
                'date' => $dueDate?->format('d-m-Y'),
                'days_remaining' => $daysRemaining,
                'is_overdue' => $daysRemaining !== null && $daysRemaining < 0,
            ],
            'package' => [
                'name' => $client->package?->name,
                'speed' => $client->package?->speed,
            ],
        ];
    }

    public function getSpeedQualityIndicator(Client $client): array
    {
        $package = $client->package;
        if (!$package) {
            return ['quality' => 'unknown', 'score' => 0];
        }

        $expectedSpeed = $this->parseSpeed($package->speed);
        $currentSpeed = max($client->current_rx_speed ?? 0, $client->current_tx_speed ?? 0);
        
        if ($expectedSpeed <= 0) {
            return ['quality' => 'unknown', 'score' => 0];
        }

        $ratio = $currentSpeed / $expectedSpeed;
        
        if ($ratio >= 0.9) {
            return ['quality' => 'excellent', 'score' => 100, 'color' => 'success'];
        } elseif ($ratio >= 0.7) {
            return ['quality' => 'good', 'score' => 80, 'color' => 'info'];
        } elseif ($ratio >= 0.5) {
            return ['quality' => 'fair', 'score' => 60, 'color' => 'warning'];
        } else {
            return ['quality' => 'poor', 'score' => 30, 'color' => 'danger'];
        }
    }

    protected function parseSpeed(string $speed): float
    {
        preg_match('/(\d+)\s*(M|Mbps|G|Gbps)?/i', $speed, $matches);
        $value = (float) ($matches[1] ?? 0);
        $unit = strtoupper($matches[2] ?? 'M');
        
        if (str_starts_with($unit, 'G')) {
            return $value * 1000 * 1000 * 1000;
        }
        return $value * 1000 * 1000;
    }

    protected function formatUptime(?int $seconds): string
    {
        if (!$seconds) return 'N/A';
        
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }

    protected function formatSpeed(?float $bitsPerSecond): string
    {
        if (!$bitsPerSecond) return '0 bps';
        
        if ($bitsPerSecond >= 1000000000) {
            return round($bitsPerSecond / 1000000000, 2) . ' Gbps';
        } elseif ($bitsPerSecond >= 1000000) {
            return round($bitsPerSecond / 1000000, 2) . ' Mbps';
        } elseif ($bitsPerSecond >= 1000) {
            return round($bitsPerSecond / 1000, 2) . ' Kbps';
        }
        return round($bitsPerSecond, 0) . ' bps';
    }

    protected function formatBytes(?int $bytes): string
    {
        if (!$bytes) return '0 B';
        
        if ($bytes >= 1099511627776) {
            return round($bytes / 1099511627776, 2) . ' TB';
        } elseif ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    public function detectAreaOutages(): array
    {
        $outages = [];
        
        $zones = DB::table('clients')
            ->select('zone_id', 
                     DB::raw('COUNT(*) as total'),
                     DB::raw("SUM(CASE WHEN connection_status = 'offline' AND status = 'active' THEN 1 ELSE 0 END) as offline"))
            ->whereNotNull('zone_id')
            ->groupBy('zone_id')
            ->havingRaw('COUNT(*) >= 5')
            ->havingRaw('offline >= total * 0.5')
            ->get();

        foreach ($zones as $zone) {
            if ($zone->offline >= 3) {
                $existingEvent = DB::table('network_event_logs')
                    ->where('event_type', 'area_outage')
                    ->where('zone_id', $zone->zone_id)
                    ->where('is_resolved', false)
                    ->first();

                if (!$existingEvent) {
                    $clientIds = DB::table('clients')
                        ->where('zone_id', $zone->zone_id)
                        ->where('connection_status', 'offline')
                        ->where('status', 'active')
                        ->pluck('id')
                        ->toArray();

                    $this->alertService->createAreaOutageAlert($zone->zone_id, $zone->offline, $clientIds);
                    
                    $this->alertService->logNetworkEvent('area_outage', [
                        'severity' => 'critical',
                        'zone_id' => $zone->zone_id,
                        'description' => "Area outage detected: {$zone->offline}/{$zone->total} clients offline",
                        'affected_count' => $zone->offline,
                        'affected_clients' => array_slice($clientIds, 0, 50),
                    ]);

                    $outages[] = [
                        'zone_id' => $zone->zone_id,
                        'total_clients' => $zone->total,
                        'offline_clients' => $zone->offline,
                    ];
                }
            }
        }

        return $outages;
    }
}
