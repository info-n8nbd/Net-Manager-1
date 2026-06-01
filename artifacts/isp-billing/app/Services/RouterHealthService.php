<?php

namespace App\Services;

use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouterHealthService
{
    protected ?MikroTikService $mikrotikService = null;

    public function __construct()
    {
        $this->mikrotikService = app(MikroTikService::class);
    }

    public function getRouterHealth(int $deviceId): array
    {
        $device = MikrotikDevice::find($deviceId);
        if (!$device) {
            return ['error' => 'Device not found'];
        }

        try {
            $api = $this->mikrotikService->connect($device);
            if (!$api) {
                return $this->getStoredHealth($deviceId);
            }

            $resources = $api->comm('/system/resource/print');
            $health = $api->comm('/system/health/print');

            $this->mikrotikService->disconnect();

            $resourceData = $resources[0] ?? [];
            $healthData = $health[0] ?? [];

            $cpuLoad = $resourceData['cpu-load'] ?? 0;
            $freeMemory = $resourceData['free-memory'] ?? 0;
            $totalMemory = $resourceData['total-memory'] ?? 1;
            $memoryUsage = round((1 - ($freeMemory / $totalMemory)) * 100, 1);
            $uptime = $resourceData['uptime'] ?? '0s';
            $temperature = $healthData['temperature'] ?? $healthData['cpu-temperature'] ?? null;

            $this->storeHealthData($deviceId, [
                'cpu_load' => $cpuLoad,
                'memory_usage' => $memoryUsage,
                'temperature' => $temperature,
                'uptime' => $uptime,
                'status' => 'online',
            ]);

            return [
                'device_id' => $deviceId,
                'name' => $device->name,
                'status' => 'online',
                'cpu_load' => $cpuLoad,
                'memory_usage' => $memoryUsage,
                'temperature' => $temperature,
                'uptime' => $uptime,
                'free_memory_mb' => round($freeMemory / 1048576, 1),
                'total_memory_mb' => round($totalMemory / 1048576, 1),
                'version' => $resourceData['version'] ?? 'unknown',
                'board_name' => $resourceData['board-name'] ?? 'unknown',
                'last_sync' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to get router health for {$device->name}: " . $e->getMessage());
            $this->createDeviceDownAlert($deviceId);
            return $this->getStoredHealth($deviceId);
        }
    }

    protected function getStoredHealth(int $deviceId): array
    {
        $stored = DB::table('router_health_logs')
            ->where('mikrotik_device_id', $deviceId)
            ->orderByDesc('created_at')
            ->first();

        $device = MikrotikDevice::find($deviceId);

        return [
            'device_id' => $deviceId,
            'name' => $device?->name,
            'status' => 'offline',
            'cpu_load' => $stored?->cpu_load ?? 0,
            'memory_usage' => $stored?->memory_usage ?? 0,
            'temperature' => $stored?->temperature,
            'uptime' => $stored?->uptime ?? '0s',
            'last_sync' => $stored?->created_at ?? null,
            'is_cached' => true,
        ];
    }

    protected function storeHealthData(int $deviceId, array $data): void
    {
        $device = MikrotikDevice::find($deviceId);
        $previousStatus = $device?->status;

        DB::table('router_health_logs')->insert([
            'mikrotik_device_id' => $deviceId,
            'cpu_load' => $data['cpu_load'],
            'memory_usage' => $data['memory_usage'],
            'temperature' => $data['temperature'],
            'uptime' => $data['uptime'],
            'status' => $data['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        MikrotikDevice::where('id', $deviceId)->update([
            'last_sync' => now(),
            'status' => $data['status'],
        ]);

        if ($device && $previousStatus && $previousStatus !== 'online' && $data['status'] === 'online') {
            $this->sendDeviceUpNotification($device, $previousStatus);
        }
    }

    protected function createDeviceDownAlert(int $deviceId): void
    {
        $device = MikrotikDevice::find($deviceId);

        $existingAlert = DB::table('network_event_logs')
            ->where('event_type', 'router_down')
            ->where('source', "router_{$deviceId}")
            ->where('is_resolved', false)
            ->exists();

        if (!$existingAlert) {
            DB::table('network_event_logs')->insert([
                'event_type' => 'router_down',
                'source' => "router_{$deviceId}",
                'message' => "Router {$device?->name} is not responding",
                'severity' => 'critical',
                'metadata' => json_encode(['device_id' => $deviceId, 'ip' => $device?->ip_address]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->sendDeviceDownNotification($device);
        }
    }

    protected function sendDeviceDownNotification(?MikrotikDevice $device): void
    {
        if (!$device) return;

        DB::table('notification_queue')->insert([
            'type' => 'router_down',
            'channel' => 'all',
            'recipient_type' => 'admin',
            'recipient_id' => null,
            'title' => 'Router Down Alert',
            'message' => "Router {$device->name} ({$device->host}) is not responding",
            'priority' => 'critical',
            'metadata' => json_encode(['device_id' => $device->id]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $affectedClients = \App\Models\Client::where('mikrotik_device_id', $device->id)->count();
            $telegram = new TelegramService();
            $telegram->sendNetworkDeviceAlert(
                $device->identity ?? $device->name ?? 'Unknown',
                'MikroTik',
                $device->host ?? '',
                'down',
                $device->zone?->name ?? '',
                $affectedClients
            );
        } catch (\Throwable $e) {
            Log::warning('Telegram device down alert failed: ' . $e->getMessage());
        }

        try {
            $affectedClients = \App\Models\Client::where('mikrotik_device_id', $device->id)->count();
            $alertService = new DeviceAlertService();
            $alertService->notifyDeviceDown(
                $device->identity ?? $device->name ?? 'Unknown',
                'MikroTik',
                $device->host ?? '',
                $affectedClients,
                $device->zone?->name ?? ''
            );
        } catch (\Throwable $e) {
            Log::warning('Email/SMS device down alert failed: ' . $e->getMessage());
        }
    }

    protected function sendDeviceUpNotification(MikrotikDevice $device, string $previousStatus): void
    {
        DB::table('network_event_logs')
            ->where('event_type', 'router_down')
            ->where('source', "router_{$device->id}")
            ->where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Device came back online automatically',
                'updated_at' => now(),
            ]);

        try {
            $downEvent = DB::table('network_event_logs')
                ->where('event_type', 'router_down')
                ->where('source', "router_{$device->id}")
                ->orderByDesc('created_at')
                ->first();

            $downtime = '';
            if ($downEvent && $downEvent->created_at) {
                $downAt = \Carbon\Carbon::parse($downEvent->created_at);
                $diff = $downAt->diff(now());
                if ($diff->days > 0) {
                    $downtime = $diff->days . 'd ' . $diff->h . 'h ' . $diff->i . 'min';
                } elseif ($diff->h > 0) {
                    $downtime = $diff->h . 'h ' . $diff->i . 'min';
                } else {
                    $downtime = $diff->i . ' min';
                }
            }

            $affectedClients = \App\Models\Client::where('mikrotik_device_id', $device->id)->count();
            $telegram = new TelegramService();
            $telegram->sendNetworkDeviceAlert(
                $device->identity ?? $device->name ?? 'Unknown',
                'MikroTik',
                $device->host ?? '',
                'online',
                $device->zone?->name ?? '',
                $affectedClients,
                $downtime
            );
        } catch (\Throwable $e) {
            Log::warning('Telegram device up alert failed: ' . $e->getMessage());
        }

        try {
            $affectedClients = \App\Models\Client::where('mikrotik_device_id', $device->id)->count();
            $alertService = new DeviceAlertService();
            $alertService->notifyDeviceUp(
                $device->identity ?? $device->name ?? 'Unknown',
                'MikroTik',
                $device->host ?? '',
                $affectedClients,
                $device->zone?->name ?? '',
                $downtime
            );
        } catch (\Throwable $e) {
            Log::warning('Email/SMS device up alert failed: ' . $e->getMessage());
        }
    }

    public function checkAllRoutersHealth(): array
    {
        $results = ['healthy' => 0, 'warning' => 0, 'critical' => 0, 'offline' => 0];
        $devices = MikrotikDevice::where('is_active', true)->get();

        foreach ($devices as $device) {
            $health = $this->getRouterHealth($device->id);

            if ($health['status'] === 'offline') {
                $results['offline']++;
            } elseif (($health['cpu_load'] ?? 0) > 90 || ($health['memory_usage'] ?? 0) > 90) {
                $results['critical']++;
            } elseif (($health['cpu_load'] ?? 0) > 70 || ($health['memory_usage'] ?? 0) > 70) {
                $results['warning']++;
            } else {
                $results['healthy']++;
            }
        }

        return $results;
    }

    public function getNetworkEventLog(int $limit = 50): array
    {
        return DB::table('network_event_logs')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'type' => $e->event_type,
                'source' => $e->source,
                'message' => $e->message,
                'severity' => $e->severity,
                'is_resolved' => (bool) $e->is_resolved,
                'created_at' => $e->created_at,
            ])
            ->toArray();
    }

    public function resolveNetworkEvent(int $eventId, string $resolution = ''): bool
    {
        return DB::table('network_event_logs')
            ->where('id', $eventId)
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => $resolution,
                'updated_at' => now(),
            ]) > 0;
    }
}
