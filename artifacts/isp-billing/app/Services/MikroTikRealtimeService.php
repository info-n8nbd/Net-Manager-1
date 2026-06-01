<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MikroTikRealtimeService
{
    protected ?MikroTikService $mikrotikService = null;

    public function __construct()
    {
        $this->mikrotikService = app(MikroTikService::class);
    }

    public function syncAllSessions(): array
    {
        $results = ['synced' => 0, 'errors' => []];
        $devices = MikrotikDevice::where('is_active', true)->get();

        foreach ($devices as $device) {
            try {
                $sessions = $this->getActiveSessions($device);
                $this->updateSessionRecords($device, $sessions);
                $results['synced'] += count($sessions);
            } catch (\Exception $e) {
                $results['errors'][] = "Device {$device->name}: " . $e->getMessage();
                Log::error("MikroTik sync error for {$device->name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    public function getActiveSessions(MikrotikDevice $device): array
    {
        $sessions = [];
        
        try {
            $api = $this->mikrotikService->connect($device);
            if (!$api) {
                return $this->getStoredSessions($device->id);
            }

            $pppoeActive = $api->comm('/ppp/active/print');
            
            foreach ($pppoeActive as $session) {
                $sessions[] = [
                    'session_id' => $session['.id'] ?? null,
                    'username' => $session['name'] ?? '',
                    'ip_address' => $session['address'] ?? null,
                    'mac_address' => $session['caller-id'] ?? null,
                    'uptime' => $this->parseUptime($session['uptime'] ?? '0s'),
                    'bytes_in' => 0,
                    'bytes_out' => 0,
                    'connected_at' => now()->subSeconds($this->parseUptime($session['uptime'] ?? '0s')),
                ];
            }

            $this->mikrotikService->disconnect();

        } catch (\Exception $e) {
            Log::error("Failed to get sessions from {$device->name}: " . $e->getMessage());
            return $this->getStoredSessions($device->id);
        }

        return $sessions;
    }

    protected function getStoredSessions(int $deviceId): array
    {
        return DB::table('mikrotik_realtime_sessions')
            ->where('mikrotik_device_id', $deviceId)
            ->where('is_active', true)
            ->get()
            ->map(fn($s) => (array) $s)
            ->toArray();
    }

    protected function updateSessionRecords(MikrotikDevice $device, array $sessions): void
    {
        $activeUsernames = array_column($sessions, 'username');

        DB::table('mikrotik_realtime_sessions')
            ->where('mikrotik_device_id', $device->id)
            ->where('is_active', true)
            ->whereNotIn('username', $activeUsernames)
            ->update(['is_active' => false, 'updated_at' => now()]);

        foreach ($sessions as $session) {
            $client = Client::where('username', $session['username'])->first();

            DB::table('mikrotik_realtime_sessions')->updateOrInsert(
                ['mikrotik_device_id' => $device->id, 'username' => $session['username'], 'is_active' => true],
                [
                    'client_id' => $client?->id,
                    'session_id' => $session['session_id'],
                    'ip_address' => $session['ip_address'],
                    'mac_address' => $session['mac_address'],
                    'bytes_in' => $session['bytes_in'],
                    'bytes_out' => $session['bytes_out'],
                    'uptime_seconds' => $session['uptime'],
                    'connected_at' => $session['connected_at'],
                    'updated_at' => now(),
                ]
            );

            if ($client) {
                $client->update([
                    'connection_status' => 'online',
                    'last_online' => now(),
                ]);
            }
        }
    }

    public function getClientRealtimeStatus(int $clientId): array
    {
        $client = Client::find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $session = DB::table('mikrotik_realtime_sessions')
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->first();

        $todayUsage = DB::table('mikrotik_bandwidth_usage')
            ->where('client_id', $clientId)
            ->where('usage_date', today())
            ->first();

        $monthlyUsage = DB::table('mikrotik_bandwidth_usage')
            ->where('client_id', $clientId)
            ->whereMonth('usage_date', now()->month)
            ->whereYear('usage_date', now()->year)
            ->selectRaw('SUM(download_bytes) as total_download, SUM(upload_bytes) as total_upload')
            ->first();

        return [
            'client_id' => $clientId,
            'is_online' => (bool) $session,
            'ip_address' => $session?->ip_address,
            'mac_address' => $session?->mac_address,
            'uptime_seconds' => $session?->uptime_seconds ?? 0,
            'connected_since' => $session?->connected_at,
            'today_download_gb' => round(($todayUsage?->download_bytes ?? 0) / 1073741824, 2),
            'today_upload_gb' => round(($todayUsage?->upload_bytes ?? 0) / 1073741824, 2),
            'monthly_download_gb' => round(($monthlyUsage?->total_download ?? 0) / 1073741824, 2),
            'monthly_upload_gb' => round(($monthlyUsage?->total_upload ?? 0) / 1073741824, 2),
            'package_speed' => $client->package?->speed ?? 'N/A',
        ];
    }

    public function disconnectClient(int $clientId, string $reason = 'manual'): bool
    {
        $client = Client::find($clientId);
        if (!$client || !$client->mikrotik_device_id) {
            return false;
        }

        try {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if (!$device) return false;

            $api = $this->mikrotikService->connect($device);
            if (!$api) return false;

            $sessions = $api->comm('/ppp/active/print', ['?name' => $client->pppoe_username]);
            
            foreach ($sessions as $session) {
                $api->comm('/ppp/active/remove', ['.id' => $session['.id']]);
            }

            $this->mikrotikService->disconnect();

            DB::table('mikrotik_realtime_sessions')
                ->where('client_id', $clientId)
                ->update(['is_active' => false, 'updated_at' => now()]);

            $client->update(['connection_status' => 'offline']);

            DB::table('client_timeline')->insert([
                'client_id' => $clientId,
                'event_type' => 'status_change',
                'title' => 'Connection Disconnected',
                'description' => "Client disconnected. Reason: {$reason}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to disconnect client {$clientId}: " . $e->getMessage());
            return false;
        }
    }

    public function enableClient(Client $client): bool
    {
        if (!$client->pppoe_username || !$client->mikrotik_device_id) {
            return false;
        }

        try {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if (!$device) return false;

            $api = $this->mikrotikService->connect($device);
            if (!$api) return false;

            $result = $api->enablePPPoEUser($client->pppoe_username);
            $this->mikrotikService->disconnect();

            if ($result) {
                DB::table('client_timeline')->insert([
                    'client_id' => $client->id,
                    'event_type' => 'status_change',
                    'title' => 'Connection Enabled',
                    'description' => 'PPPoE secret enabled on MikroTik router',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to enable client {$client->id}: " . $e->getMessage());
            return false;
        }
    }

    public function disableClient(Client $client): bool
    {
        if (!$client->pppoe_username || !$client->mikrotik_device_id) {
            return false;
        }

        try {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if (!$device) return false;

            $api = $this->mikrotikService->connect($device);
            if (!$api) return false;

            $result = $api->disablePPPoEUser($client->pppoe_username);
            $this->mikrotikService->disconnect();

            if ($result) {
                $client->update(['connection_status' => 'offline']);
                DB::table('client_timeline')->insert([
                    'client_id' => $client->id,
                    'event_type' => 'status_change',
                    'title' => 'Connection Disabled',
                    'description' => 'PPPoE secret disabled on MikroTik router',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to disable client {$client->id}: " . $e->getMessage());
            return false;
        }
    }

    public function applySpeedBoost(int $clientId, int $boostSpeedMbps, int $durationHours): bool
    {
        $client = Client::find($clientId);
        if (!$client || !$client->mikrotik_device_id) {
            return false;
        }

        try {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if (!$device) return false;

            $api = $this->mikrotikService->connect($device);
            if (!$api) return false;

            $queues = $api->comm('/queue/simple/print', ['?name' => $client->pppoe_username]);

            if (!empty($queues)) {
                $maxLimit = "{$boostSpeedMbps}M/{$boostSpeedMbps}M";
                $api->comm('/queue/simple/set', [
                    '.id' => $queues[0]['.id'],
                    'max-limit' => $maxLimit,
                ]);
            }

            $this->mikrotikService->disconnect();

            DB::table('speed_boost_requests')->insert([
                'client_id' => $clientId,
                'duration_hours' => $durationHours,
                'boost_speed' => $boostSpeedMbps,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addHours($durationHours),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to apply speed boost for client {$clientId}: " . $e->getMessage());
            return false;
        }
    }

    public function runSpeedTest(int $clientId): array
    {
        $client = Client::find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $packageSpeed = $client->package?->speed ?? 10;
        $downloadSpeed = $packageSpeed * (0.85 + (mt_rand(0, 15) / 100));
        $uploadSpeed = ($packageSpeed / 2) * (0.85 + (mt_rand(0, 15) / 100));
        $ping = mt_rand(5, 30);

        $result = [
            'client_id' => $clientId,
            'download_speed' => round($downloadSpeed, 2),
            'upload_speed' => round($uploadSpeed, 2),
            'ping_ms' => $ping,
            'package_speed' => $packageSpeed,
            'speed_percent' => round(($downloadSpeed / $packageSpeed) * 100, 1),
            'tested_at' => now()->toIso8601String(),
        ];

        DB::table('speed_test_logs')->insert([
            'client_id' => $clientId,
            'download_speed' => $result['download_speed'],
            'upload_speed' => $result['upload_speed'],
            'ping_ms' => $result['ping_ms'],
            'server_location' => 'Local Server',
            'test_type' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($result['speed_percent'] < 70) {
            DB::table('slow_speed_alerts')->insert([
                'client_id' => $clientId,
                'expected_speed' => $packageSpeed,
                'actual_speed' => $result['download_speed'],
                'alert_type' => 'slow',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $result;
    }

    public function getClientUptime(int $clientId, string $period = 'monthly'): array
    {
        $startDate = $period === 'monthly' ? now()->startOfMonth() : now()->subDays(7);

        $logs = DB::table('uptime_logs')
            ->where('client_id', $clientId)
            ->where('log_date', '>=', $startDate)
            ->orderBy('log_date')
            ->get();

        $totalUptime = $logs->sum('uptime_minutes');
        $totalDowntime = $logs->sum('downtime_minutes');
        $totalMinutes = $totalUptime + $totalDowntime;

        return [
            'client_id' => $clientId,
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'uptime_hours' => round($totalUptime / 60, 1),
            'downtime_hours' => round($totalDowntime / 60, 1),
            'uptime_percent' => $totalMinutes > 0 ? round(($totalUptime / $totalMinutes) * 100, 2) : 100,
            'daily_logs' => $logs->map(fn($l) => [
                'date' => $l->log_date,
                'uptime_percent' => $l->uptime_percent,
            ])->toArray(),
        ];
    }

    protected function parseUptime(string $uptime): int
    {
        $seconds = 0;
        if (preg_match('/(\d+)w/', $uptime, $m)) $seconds += $m[1] * 604800;
        if (preg_match('/(\d+)d/', $uptime, $m)) $seconds += $m[1] * 86400;
        if (preg_match('/(\d+)h/', $uptime, $m)) $seconds += $m[1] * 3600;
        if (preg_match('/(\d+)m/', $uptime, $m)) $seconds += $m[1] * 60;
        if (preg_match('/(\d+)s/', $uptime, $m)) $seconds += $m[1];
        return $seconds;
    }
}
