<?php

namespace App\Services;

use App\Models\MikrotikDevice;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NetworkMonitoringService
{
    protected MikroTikService $mikrotikService;

    public function __construct()
    {
        $this->mikrotikService = new MikroTikService();
    }

    public function getPopStats(): array
    {
        $cacheKey = 'pop_stats_' . now()->format('Y-m-d-H-i');
        
        return Cache::remember($cacheKey, 60, function () {
            $devices = MikrotikDevice::where('is_active', true)->get();
            $stats = [];

            foreach ($devices as $device) {
                try {
                    $api = MikroTikService::fromDevice($device);
                    if ($api->connect()) {
                        $resources = $api->getSystemResources();
                        $activeUsers = $api->getPPPoEActiveUsers();
                        $interfaces = $api->getInterfaces();

                        $stats[] = [
                            'device_id' => $device->id,
                            'device_name' => $device->name,
                            'ip_address' => $device->ip_address,
                            'location' => $device->location,
                            'status' => 'online',
                            'uptime' => $resources['uptime'] ?? 'N/A',
                            'cpu_load' => $resources['cpu-load'] ?? 0,
                            'memory_usage' => $this->calculateMemoryUsage($resources),
                            'active_users' => count($activeUsers),
                            'total_clients' => $this->getDeviceClientCount($device->id),
                            'interface_count' => count($interfaces),
                            'version' => $resources['version'] ?? 'Unknown',
                            'board_name' => $resources['board-name'] ?? 'Unknown',
                            'last_check' => now()->toIso8601String(),
                        ];

                        $api->disconnect();
                    } else {
                        $stats[] = $this->getOfflineDeviceStats($device);
                    }
                } catch (\Exception $e) {
                    Log::error("POP stats error for {$device->name}: " . $e->getMessage());
                    $stats[] = $this->getOfflineDeviceStats($device);
                }
            }

            return $stats;
        });
    }

    protected function calculateMemoryUsage(array $resources): int
    {
        $total = $resources['total-memory'] ?? 0;
        $free = $resources['free-memory'] ?? 0;
        
        if ($total <= 0) return 0;
        
        return round(($total - $free) / $total * 100);
    }

    protected function getDeviceClientCount(int $deviceId): int
    {
        return Client::where('mikrotik_device_id', $deviceId)
            ->where('status', 'active')
            ->count();
    }

    protected function getOfflineDeviceStats(MikrotikDevice $device): array
    {
        return [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'ip_address' => $device->ip_address,
            'location' => $device->location,
            'status' => 'offline',
            'uptime' => 'N/A',
            'cpu_load' => 0,
            'memory_usage' => 0,
            'active_users' => 0,
            'total_clients' => $this->getDeviceClientCount($device->id),
            'interface_count' => 0,
            'version' => 'Unknown',
            'board_name' => 'Unknown',
            'last_check' => now()->toIso8601String(),
            'error' => 'Device unreachable',
        ];
    }

    public function syncClientSpeed(Client $client): array
    {
        if (!$client->pppoe_username || !$client->package) {
            return ['success' => false, 'message' => 'Client missing PPPoE username or package'];
        }

        try {
            $device = $client->mikrotikDevice ?? MikrotikDevice::where('is_active', true)->first();
            if (!$device) {
                return ['success' => false, 'message' => 'No MikroTik device found'];
            }

            $api = MikroTikService::fromDevice($device);
            if (!$api->connect()) {
                return ['success' => false, 'message' => 'Failed to connect to MikroTik'];
            }

            $profileName = $client->package->mikrotik_profile ?? $client->package->name;
            
            $result = $api->updatePPPoEUser($client->pppoe_username, [
                'profile' => $profileName,
            ]);

            $api->disconnect();

            if ($result) {
                $client->update([
                    'speed_synced_at' => now(),
                    'last_sync_status' => 'success',
                ]);

                \App\Models\ClientActivityLog::log(
                    $client->id,
                    'network',
                    'speed_sync',
                    "Speed profile synced to {$profileName}",
                    ['profile' => $profileName, 'device' => $device->name],
                    auth()->id()
                );

                return ['success' => true, 'message' => 'Speed synced successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update user profile'];
        } catch (\Exception $e) {
            Log::error("Speed sync error for client {$client->id}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function autoDowngradeOverdueClients(int $graceDays = 7): array
    {
        $cutoffDate = now()->subDays($graceDays);
        
        $overdueClients = Client::where('status', 'active')
            ->where('due_amount', '>', 0)
            ->whereHas('bills', function ($q) use ($cutoffDate) {
                $q->where('status', 'unpaid')
                    ->where('due_date', '<', $cutoffDate);
            })
            ->get();

        $downgraded = 0;
        $inactivated = 0;
        $errors = [];

        foreach ($overdueClients as $client) {
            try {
                $overdueDays = $client->bills()
                    ->where('status', 'unpaid')
                    ->min('due_date');
                
                $overdueDays = $overdueDays ? now()->diffInDays($overdueDays) : 0;

                if ($overdueDays >= 30) {
                    $result = app(MikroTikService::class)->suspendClient(
                        $client,
                        'auto_downgrade_overdue_30days',
                        null,
                        true
                    );
                    if ($result['success']) {
                        $inactivated++;
                    }
                } elseif ($overdueDays >= 15) {
                    if ($this->downgradeClientSpeed($client)) {
                        $downgraded++;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => count($overdueClients),
            'downgraded' => $downgraded,
            'inactive' => $inactivated,
            'errors' => $errors,
        ];
    }

    protected function downgradeClientSpeed(Client $client): bool
    {
        try {
            $device = $client->mikrotikDevice ?? MikrotikDevice::where('is_active', true)->first();
            if (!$device) return false;

            $api = MikroTikService::fromDevice($device);
            if (!$api->connect()) return false;

            $limitedProfile = config('isp.limited_profile', 'limited-512k');
            
            $result = $api->updatePPPoEUser($client->pppoe_username, [
                'profile' => $limitedProfile,
            ]);

            $api->disconnect();

            if ($result) {
                $client->update([
                    'is_speed_limited' => true,
                    'speed_limited_at' => now(),
                ]);

                \App\Models\ClientActivityLog::log(
                    $client->id,
                    'network',
                    'auto_downgrade',
                    "Speed downgraded to {$limitedProfile} due to overdue payment",
                    ['original_package' => $client->package?->name],
                    null
                );

                app(SmsService::class)->sendSpeedLimitNotification($client);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Auto downgrade error for client {$client->id}: " . $e->getMessage());
            return false;
        }
    }

    public function restoreClientSpeed(Client $client): bool
    {
        if (!$client->is_speed_limited) {
            return true;
        }

        $result = $this->syncClientSpeed($client);
        
        if ($result['success']) {
            $client->update([
                'is_speed_limited' => false,
                'speed_limited_at' => null,
            ]);
            return true;
        }

        return false;
    }

    public function getBandwidthStats(): array
    {
        $devices = MikrotikDevice::where('is_active', true)->get();
        $stats = [];

        foreach ($devices as $device) {
            try {
                $api = MikroTikService::fromDevice($device);
                if ($api->connect()) {
                    $interfaces = $api->getInterfaces();
                    $wanInterface = collect($interfaces)->firstWhere('name', 'ether1');
                    
                    $stats[$device->name] = [
                        'rx_bytes' => $wanInterface['rx-byte'] ?? 0,
                        'tx_bytes' => $wanInterface['tx-byte'] ?? 0,
                        'rx_rate' => $this->formatBandwidth($wanInterface['rx-byte'] ?? 0),
                        'tx_rate' => $this->formatBandwidth($wanInterface['tx-byte'] ?? 0),
                    ];
                    
                    $api->disconnect();
                }
            } catch (\Exception $e) {
                Log::error("Bandwidth stats error for {$device->name}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    protected function formatBandwidth(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' Gbps';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' Mbps';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' Kbps';
        }
        return $bytes . ' bps';
    }

    public function getMikrotikRetryStats(): array
    {
        return [
            'pending' => \App\Models\MikrotikActionLog::where('status', 'pending')->count(),
            'retrying' => \App\Models\MikrotikActionLog::where('status', 'retrying')->count(),
            'failed' => \App\Models\MikrotikActionLog::where('status', 'failed')->count(),
            'success_today' => \App\Models\MikrotikActionLog::where('status', 'success')
                ->whereDate('created_at', today())
                ->count(),
            'failed_today' => \App\Models\MikrotikActionLog::where('status', 'failed')
                ->whereDate('created_at', today())
                ->count(),
        ];
    }
}
