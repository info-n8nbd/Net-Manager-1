<?php

namespace App\Services;

use App\Models\MikrotikDevice;
use App\Models\OltDevice;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NetworkIntegrationService
{
    protected SimpleMikroTikService $mikrotikApi;

    public function __construct()
    {
        $this->mikrotikApi = new SimpleMikroTikService();
    }

    public function getRouterStatus(int $deviceId): array
    {
        $device = MikrotikDevice::find($deviceId);
        if (!$device) {
            return ['success' => false, 'message' => 'Device not found'];
        }

        try {
            $api = MikroTikService::fromDevice($device);
            if (!$api->connect()) {
                $device->update(['status' => 'offline', 'last_checked_at' => now()]);
                return [
                    'success' => false,
                    'status' => 'offline',
                    'device' => $device->toArray(),
                    'message' => 'Cannot connect to router',
                ];
            }

            $resources = $api->getSystemResources();
            $identity = $api->getSystemIdentity();
            $interfaces = $api->getInterfaces();
            $activeSessions = $api->getActiveSessions();
            $api->disconnect();

            $updateData = [
                'status' => 'online',
                'last_checked_at' => now(),
            ];

            if ($identity) {
                $updateData['system_identity'] = $identity;
            }
            if (is_array($resources)) {
                if (isset($resources['uptime'])) $updateData['uptime'] = $resources['uptime'];
                if (isset($resources['cpu-load'])) $updateData['cpu_load'] = (int) $resources['cpu-load'];
                if (isset($resources['version'])) $updateData['version'] = $resources['version'];
                if (isset($resources['free-memory'], $resources['total-memory'])) {
                    $total = (int) $resources['total-memory'];
                    $free = (int) $resources['free-memory'];
                    $updateData['memory_usage'] = $total > 0 ? (int) round((1 - $free / $total) * 100) : 0;
                }
            }

            $device->update($updateData);

            return [
                'success' => true,
                'status' => 'online',
                'device' => $device->fresh()->toArray(),
                'identity' => $identity,
                'resources' => $resources,
                'interfaces' => $interfaces,
                'active_sessions' => count($activeSessions),
                'sessions' => $activeSessions,
            ];
        } catch (\Exception $e) {
            Log::error("getRouterStatus failed for device {$deviceId}: " . $e->getMessage());
            $device->update(['status' => 'offline', 'last_checked_at' => now()]);
            return [
                'success' => false,
                'status' => 'offline',
                'device' => $device->toArray(),
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getRouterInterfaces(int $deviceId): array
    {
        $device = MikrotikDevice::find($deviceId);
        if (!$device) return [];

        try {
            $api = MikroTikService::fromDevice($device);
            if (!$api->connect()) return [];

            $interfaces = $api->getInterfaces();
            $api->disconnect();
            return $interfaces;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPPPSecrets(int $deviceId): array
    {
        $device = MikrotikDevice::find($deviceId);
        if (!$device) return [];

        try {
            $api = MikroTikService::fromDevice($device);
            if (!$api->connect()) return [];

            $secrets = $api->getAllPPPoESecrets();
            $api->disconnect();
            return $secrets;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function syncPPPUsers(int $deviceId): array
    {
        $device = MikrotikDevice::find($deviceId);
        if (!$device) {
            return ['success' => false, 'message' => 'Device not found'];
        }

        try {
            $api = MikroTikService::fromDevice($device);
            if (!$api->connect()) {
                return ['success' => false, 'message' => 'Cannot connect to router'];
            }

            $routerSecrets = $api->getAllPPPoESecrets();
            $api->disconnect();

            $billingUsers = Client::where('connection_type', 'pppoe')
                ->whereNotNull('username')
                ->where('status', '!=', 'deleted')
                ->get();

            $routerUsernames = collect($routerSecrets)->pluck('name')->toArray();
            $billingUsernames = $billingUsers->pluck('username')->toArray();

            $onlyOnRouter = array_diff($routerUsernames, $billingUsernames);
            $onlyInBilling = array_diff($billingUsernames, $routerUsernames);
            $synced = array_intersect($routerUsernames, $billingUsernames);

            return [
                'success' => true,
                'total_router' => count($routerSecrets),
                'total_billing' => count($billingUsernames),
                'synced' => count($synced),
                'only_on_router' => array_values($onlyOnRouter),
                'only_in_billing' => array_values($onlyInBilling),
                'message' => 'Sync comparison complete',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function pushSpeedProfile(string $username, string $profile, int $deviceId): array
    {
        $device = MikrotikDevice::find($deviceId);
        if (!$device) {
            return ['success' => false, 'message' => 'Device not found'];
        }

        $api = new SimpleMikroTikService();
        if (!$api->connectFromDevice($device)) {
            return ['success' => false, 'message' => 'Cannot connect to router'];
        }

        $result = $api->updatePPPoEProfile($username, $profile);
        $api->disconnect();
        return $result;
    }

    public function suspendUser(string $username, int $deviceId): array
    {
        $device = MikrotikDevice::find($deviceId);
        if (!$device) {
            return ['success' => false, 'message' => 'Device not found'];
        }

        $api = new SimpleMikroTikService();
        if (!$api->connectFromDevice($device)) {
            return ['success' => false, 'message' => 'Cannot connect to router'];
        }

        $result = $api->disablePPPoESecret($username);
        $api->disconnect();
        return $result;
    }

    public function unsuspendUser(string $username, int $deviceId): array
    {
        $device = MikrotikDevice::find($deviceId);
        if (!$device) {
            return ['success' => false, 'message' => 'Device not found'];
        }

        $api = new SimpleMikroTikService();
        if (!$api->connectFromDevice($device)) {
            return ['success' => false, 'message' => 'Cannot connect to router'];
        }

        $result = $api->enablePPPoESecret($username);
        $api->disconnect();
        return $result;
    }

    public function getOLTStatus(int $oltId): array
    {
        $olt = OltDevice::find($oltId);
        if (!$olt) {
            return ['success' => false, 'message' => 'OLT not found'];
        }

        $totalOnus = DB::table('onus')->where('olt_device_id', $oltId)->count();
        $onlineOnus = DB::table('onus')->where('olt_device_id', $oltId)->where('status', 'online')->count();
        $offlineOnus = DB::table('onus')->where('olt_device_id', $oltId)->where('status', 'offline')->count();
        $alarmCount = DB::table('onu_alarm_logs')->where('olt_device_id', $oltId)->where('is_resolved', false)->count();

        return [
            'success' => true,
            'olt' => $olt->toArray(),
            'total_onus' => $totalOnus,
            'online_onus' => $onlineOnus,
            'offline_onus' => $offlineOnus,
            'active_alarms' => $alarmCount,
        ];
    }

    public function getNetworkSummary(): array
    {
        $totalRouters = MikrotikDevice::count();
        $onlineRouters = MikrotikDevice::where('status', 'online')->count();
        $offlineRouters = MikrotikDevice::where('status', 'offline')->count();
        $coreRouters = MikrotikDevice::where('router_type', 'core')->count();
        $nocRouters = MikrotikDevice::where('router_type', 'noc')->count();
        $popRouters = MikrotikDevice::where('router_type', 'pop')->count();
        $branchRouters = MikrotikDevice::where('router_type', 'branch')->count();

        $totalOlts = OltDevice::count();
        $activeOlts = OltDevice::where('is_active', true)->count();

        $totalOnus = DB::table('onus')->count();
        $onlineOnus = DB::table('onus')->where('status', 'online')->count();
        $offlineOnus = DB::table('onus')->where('status', 'offline')->count();

        $activeAlarms = DB::table('onu_alarm_logs')->where('is_resolved', false)->count();

        $recentEvents = DB::table('network_event_logs')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->toArray();

        $routerHealthData = MikrotikDevice::where('is_active', true)
            ->whereNotNull('cpu_load')
            ->select('id', 'name', 'router_type', 'status', 'cpu_load', 'memory_usage', 'uptime', 'version', 'last_checked_at')
            ->get()
            ->toArray();

        return [
            'routers' => [
                'total' => $totalRouters,
                'online' => $onlineRouters,
                'offline' => $offlineRouters,
                'core' => $coreRouters,
                'noc' => $nocRouters,
                'pop' => $popRouters,
                'branch' => $branchRouters,
            ],
            'olts' => [
                'total' => $totalOlts,
                'active' => $activeOlts,
            ],
            'onus' => [
                'total' => $totalOnus,
                'online' => $onlineOnus,
                'offline' => $offlineOnus,
            ],
            'active_alarms' => $activeAlarms,
            'recent_events' => $recentEvents,
            'router_health' => $routerHealthData,
        ];
    }
}
