<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MikroTikRealtimeService;
use App\Services\MikroTikService;
use App\Services\OltRealtimeService;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\OltDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealtimeSyncController extends Controller
{
    protected MikroTikRealtimeService $mikrotikService;
    protected OltRealtimeService $oltService;

    public function __construct(MikroTikRealtimeService $mikrotikService, OltRealtimeService $oltService)
    {
        $this->mikrotikService = $mikrotikService;
        $this->oltService = $oltService;
    }

    public function syncAllDevices()
    {
        $results = [
            'mikrotik' => ['synced' => 0, 'errors' => []],
            'olt' => ['polled' => 0, 'onus_updated' => 0, 'errors' => []],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];

        try {
            $results['mikrotik'] = $this->mikrotikService->syncAllSessions();
        } catch (\Exception $e) {
            $results['mikrotik']['errors'][] = $e->getMessage();
            Log::error('MikroTik sync failed: ' . $e->getMessage());
        }

        try {
            $results['olt'] = $this->oltService->pollAllOlts();
        } catch (\Exception $e) {
            $results['olt']['errors'][] = $e->getMessage();
            Log::error('OLT poll failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => sprintf(
                'Synced %d MikroTik sessions, updated %d ONUs',
                $results['mikrotik']['synced'],
                $results['olt']['onus_updated']
            ),
        ]);
    }

    public function instantSyncClientStatuses(Request $request)
    {
        $startTime = microtime(true);
        $onlineUsernames = [];
        $errors = [];
        $devicesChecked = 0;

        $devices = MikrotikDevice::where('is_active', true)->get();

        foreach ($devices as $device) {
            try {
                $service = new MikroTikService(
                    $device->getRawOriginal('host') ?? $device->host,
                    $device->username,
                    $device->password,
                    (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
                );

                if (!$service->connect()) {
                    $errors[] = "Cannot connect to {$device->name}";
                    continue;
                }

                $activeSessions = $service->getPPPoEActiveUsers();
                $devicesChecked++;

                foreach ($activeSessions as $session) {
                    $username = $session['name'] ?? '';
                    if (empty($username)) continue;

                    $onlineUsernames[$username] = [
                        'address' => $session['address'] ?? null,
                        'caller_id' => $session['caller-id'] ?? null,
                        'uptime' => $session['uptime'] ?? null,
                        'device_id' => $device->id,
                    ];
                }

                $service->disconnect();
            } catch (\Exception $e) {
                $errors[] = "Error on {$device->name}: " . $e->getMessage();
                Log::error("Instant sync error for device {$device->name}: " . $e->getMessage());
            }
        }

        $clientIdFilter = $request->input('client_id');

        // --- SimpleMikroTikService fallback for single-client profile view ---
        // MikroTikService may fail to connect to some devices (e.g. non-standard ports).
        // When viewing a specific client's profile, do a direct check on their assigned
        // device using SimpleMikroTikService if their username wasn't found in the scan.
        if ($clientIdFilter) {
            $targetClient = Client::select('id', 'username', 'mikrotik_device_id')
                ->find($clientIdFilter);
            if ($targetClient && $targetClient->mikrotik_device_id
                    && !isset($onlineUsernames[$targetClient->username])) {
                try {
                    $assignedDevice = MikrotikDevice::find($targetClient->mikrotik_device_id);
                    if ($assignedDevice) {
                        $simpleService = new \App\Services\SimpleMikroTikService();
                        $connected = $simpleService->connect(
                            $assignedDevice->getRawOriginal('host') ?? $assignedDevice->host,
                            $assignedDevice->username,
                            $assignedDevice->password,
                            (int)($assignedDevice->getRawOriginal('port') ?? $assignedDevice->port ?? 8728),
                            4
                        );
                        if ($connected) {
                            $session = $simpleService->getActiveSession($targetClient->username);
                            if ($session) {
                                $onlineUsernames[$targetClient->username] = [
                                    'address'   => $session['address']     ?? null,
                                    'caller_id' => $session['caller-id']   ?? null,
                                    'uptime'    => $session['uptime']      ?? null,
                                    'device_id' => $assignedDevice->id,
                                ];
                            }
                            $simpleService->disconnect();
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("SimpleMikroTik fallback check failed for client {$clientIdFilter}: " . $e->getMessage());
                }
            }
        }
        // --- end fallback ---

        $clientQuery = Client::where('status', '!=', 'deleted');
        if ($clientIdFilter) {
            $clientQuery->where('id', $clientIdFilter);
        }
        $clients = $clientQuery->get(['id', 'username', 'connection_status']);

        $updatedOnline = 0;
        $updatedOffline = 0;
        $clientStatuses = [];

        foreach ($clients as $client) {
            if (!$client->username) continue;

            if (isset($onlineUsernames[$client->username])) {
                $sessionData = $onlineUsernames[$client->username];
                if ($client->connection_status !== 'online') {
                    $client->update([
                        'connection_status' => 'online',
                        'last_online' => now(),
                        'session_ip' => $sessionData['address'],
                        'session_mac' => $sessionData['caller_id'],
                        'session_uptime' => $sessionData['uptime'],
                    ]);
                    $updatedOnline++;
                } else {
                    $client->update([
                        'last_online' => now(),
                        'session_ip' => $sessionData['address'],
                        'session_mac' => $sessionData['caller_id'],
                        'session_uptime' => $sessionData['uptime'],
                    ]);
                }
                $clientStatuses[$client->id] = [
                    'status' => 'online',
                    'ip' => $sessionData['address'],
                    'uptime' => $sessionData['uptime'],
                    'mac' => $sessionData['caller_id'],
                ];
            } else {
                if ($client->connection_status === 'online') {
                    $client->update([
                        'connection_status' => 'offline',
                        'session_ip' => null,
                        'session_mac' => null,
                        'session_uptime' => null,
                        'last_logout' => now(),
                    ]);
                    $updatedOffline++;
                }
                $clientStatuses[$client->id] = ['status' => 'offline'];
            }
        }

        $executionTime = round((microtime(true) - $startTime) * 1000);

        $user = auth()->user();
        $isSuperAdminSync = $user && ($user->user_type === 'super_admin' || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')));
        $onlineBase = Client::where('connection_status', 'online')->where('status', '!=', 'deleted');
        $offlineBase = Client::where('status', '!=', 'deleted')->whereNull('reseller_id')->where('connection_status', '!=', 'online');
        if ($isSuperAdminSync && $request->filled('company_id')) {
            $onlineBase->where('company_id', $request->company_id);
            $offlineBase->where('company_id', $request->company_id);
        } elseif ($isSuperAdminSync && $request->filled('all_co')) {
            // all companies - no filter
        } elseif ($isSuperAdminSync) {
            $onlineBase->where('company_id', $user->company_id);
            $offlineBase->where('company_id', $user->company_id);
        } elseif ($user) {
            $onlineBase->where('company_id', $user->company_id);
            $offlineBase->where('company_id', $user->company_id);
        }
        $totalOnline = $onlineBase->count();
        $totalOffline = $offlineBase->count();

        return response()->json([
            'success' => true,
            'devices_checked' => $devicesChecked,
            'updated_online' => $updatedOnline,
            'updated_offline' => $updatedOffline,
            'total_online' => $totalOnline,
            'total_offline' => $totalOffline,
            'client_statuses' => $clientStatuses,
            'execution_time_ms' => $executionTime,
            'errors' => $errors,
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function syncMikrotikDevice(MikrotikDevice $device)
    {
        try {
            $sessions = $this->mikrotikService->getActiveSessions($device);
            
            return response()->json([
                'success' => true,
                'device' => $device->name,
                'sessions_count' => count($sessions),
                'sessions' => array_slice($sessions, 0, 20),
                'timestamp' => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncOltDevice(OltDevice $olt)
    {
        try {
            $onuData = $this->oltService->pollOltOnus($olt);
            
            return response()->json([
                'success' => true,
                'olt' => $olt->name,
                'onus_count' => count($onuData),
                'onus' => array_slice($onuData, 0, 20),
                'timestamp' => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getClientRealtimeStatus(Client $client)
    {
        $status = $this->mikrotikService->getClientRealtimeStatus($client->id);
        
        $onuStatus = null;
        if ($client->onu_device_id) {
            $onuStatus = $this->oltService->getOnuRealtimeStatus($client->onu_device_id);
        }

        return response()->json([
            'client_id' => $client->id,
            'name' => $client->name,
            'pppoe' => $status,
            'onu' => $onuStatus,
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function getNetworkOverview()
    {
        $mikrotikDevices = MikrotikDevice::where('is_active', true)->get();
        $oltDevices = OltDevice::where('is_active', true)->get();

        $mikrotikStats = [];
        foreach ($mikrotikDevices as $device) {
            $onlineClients = Client::where('mikrotik_device_id', $device->id)
                ->where('connection_status', 'online')
                ->count();
            $totalClients = Client::where('mikrotik_device_id', $device->id)->count();
            
            $mikrotikStats[] = [
                'id' => $device->id,
                'name' => $device->name,
                'ip' => $device->host,
                'online_clients' => $onlineClients,
                'total_clients' => $totalClients,
                'status' => $device->last_sync_at && $device->last_sync_at > now()->subMinutes(5) ? 'online' : 'offline',
                'last_sync' => $device->last_sync_at?->diffForHumans() ?? 'Never',
            ];
        }

        $oltStats = [];
        foreach ($oltDevices as $olt) {
            $onuStats = DB::table('onu_devices')
                ->where('olt_device_id', $olt->id)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as online', ['online'])
                ->first();
            
            $oltStats[] = [
                'id' => $olt->id,
                'name' => $olt->name,
                'ip' => $olt->ip_address,
                'online_onus' => $onuStats->online ?? 0,
                'total_onus' => $onuStats->total ?? 0,
                'status' => $olt->is_active ? 'online' : 'offline',
            ];
        }

        $totalOnlineClients = Client::where('connection_status', 'online')->count();
        $totalOfflineClients = Client::where('status', '!=', 'deleted')->whereNull('reseller_id')->where('connection_status', 'offline')->count();

        return response()->json([
            'mikrotik' => [
                'devices' => $mikrotikStats,
                'total_devices' => count($mikrotikStats),
                'online_devices' => count(array_filter($mikrotikStats, fn($d) => $d['status'] === 'online')),
            ],
            'olt' => [
                'devices' => $oltStats,
                'total_devices' => count($oltStats),
            ],
            'clients' => [
                'online' => $totalOnlineClients,
                'offline' => $totalOfflineClients,
                'total_active' => $totalOnlineClients + $totalOfflineClients,
            ],
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function getRealtimeAlerts()
    {
        $alerts = [];

        $fiberCuts = $this->oltService->detectFiberCuts();
        foreach ($fiberCuts as $cut) {
            $alerts[] = [
                'type' => 'fiber_cut',
                'severity' => 'critical',
                'title' => 'Fiber Cut Detected',
                'message' => "Port {$cut['port']} on {$cut['olt_name']}: {$cut['offline_count']}/{$cut['total_onus']} ONUs offline",
                'probability' => $cut['probability'],
            ];
        }

        $offlineRouters = MikrotikDevice::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('last_sync_at')
                  ->orWhere('last_sync_at', '<', now()->subMinutes(10));
            })
            ->get();

        foreach ($offlineRouters as $router) {
            $alerts[] = [
                'type' => 'router_offline',
                'severity' => 'critical',
                'title' => 'Router Offline',
                'message' => "{$router->name} ({$router->host}) not responding",
            ];
        }

        $lowSignalOnus = DB::table('onu_devices')
            ->where('rx_power', '<', -28)
            ->join('clients', 'onu_devices.client_id', '=', 'clients.id')
            ->select('onu_devices.*', 'clients.name as client_name')
            ->limit(10)
            ->get();

        foreach ($lowSignalOnus as $onu) {
            $alerts[] = [
                'type' => 'low_signal',
                'severity' => $onu->rx_power < -30 ? 'critical' : 'warning',
                'title' => 'Low Signal',
                'message' => "{$onu->client_name}: {$onu->rx_power}dBm",
            ];
        }

        return response()->json([
            'alerts' => $alerts,
            'total_critical' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
            'total_warning' => count(array_filter($alerts, fn($a) => $a['severity'] === 'warning')),
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function getLiveBandwidthStats()
    {
        $activeSessions = DB::table('mikrotik_realtime_sessions')
            ->where('is_active', true)
            ->orderByDesc('bytes_in')
            ->limit(20)
            ->get();

        $topUsers = [];
        foreach ($activeSessions as $session) {
            $client = Client::find($session->client_id);
            if ($client) {
                $topUsers[] = [
                    'client_id' => $client->id,
                    'name' => $client->name,
                    'username' => $session->username,
                    'ip' => $session->ip_address,
                    'download_mb' => round($session->bytes_in / 1048576, 2),
                    'upload_mb' => round($session->bytes_out / 1048576, 2),
                    'uptime_hours' => round($session->uptime_seconds / 3600, 1),
                ];
            }
        }

        $totalDownload = DB::table('mikrotik_bandwidth_usage')
            ->whereDate('usage_date', today())
            ->sum('download_bytes');

        $totalUpload = DB::table('mikrotik_bandwidth_usage')
            ->whereDate('usage_date', today())
            ->sum('upload_bytes');

        return response()->json([
            'top_users' => $topUsers,
            'today_download_gb' => round($totalDownload / 1073741824, 2),
            'today_upload_gb' => round($totalUpload / 1073741824, 2),
            'active_sessions' => $activeSessions->count(),
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function syncAllOnusNow(\Illuminate\Http\Request $request)
    {
        // Extend PHP execution time: GPON Telnet may take a few seconds per OLT
        @set_time_limit(90);

        $startTime = microtime(true);
        $errors    = [];

        // ── GPON Telnet sync: runs synchronously (fast, Telnet-based) ──────────
        try {
            \App\Jobs\PollGponTelnetJob::dispatchSync();
        } catch (\Exception $e) {
            $errors[] = 'GPON: ' . $e->getMessage();
            Log::error('syncAllOnusNow GPON error: ' . $e->getMessage());
        }

        // ── SNMP/ONU-status sync: can take 160s+ → queue only ─────────────────
        try {
            \App\Jobs\PollOltOnuStatusJob::dispatch();
        } catch (\Exception $e) {
            $errors[] = 'SNMP dispatch: ' . $e->getMessage();
            Log::error('syncAllOnusNow SNMP dispatch error: ' . $e->getMessage());
        }

        $elapsed      = round(microtime(true) - $startTime, 1);
        $onlineCount  = DB::table('onus')->where('status', 'online')->count();
        $offlineCount = DB::table('onus')->where('status', 'offline')->count();

        return response()->json([
            'success'       => true,
            'elapsed_sec'   => $elapsed,
            'online_count'  => $onlineCount,
            'offline_count' => $offlineCount,
            'errors'        => $errors,
            'timestamp'     => now()->format('H:i:s'),
        ]);
    }

    public function getOltSyncStatus(\Illuminate\Http\Request $request)
    {
        $requestedAt = $request->input('requested_at');

        $completed     = false;
        $lastCompleted = null;

        if ($requestedAt) {
            try {
                $reqTime = \Carbon\Carbon::parse($requestedAt);

                $latestGpon = DB::table('cron_job_logs')
                    ->where('job_name', 'poll_gpon_telnet')
                    ->where('status', 'completed')
                    ->orderByDesc('completed_at')
                    ->first(['completed_at']);

                $latestSnmp = DB::table('cron_job_logs')
                    ->where('job_name', 'poll_olt_onu_status')
                    ->where('status', 'completed')
                    ->orderByDesc('completed_at')
                    ->first(['completed_at']);

                $gponDone = $latestGpon
                    && \Carbon\Carbon::parse($latestGpon->completed_at)->greaterThan($reqTime);
                $snmpDone = $latestSnmp
                    && \Carbon\Carbon::parse($latestSnmp->completed_at)->greaterThan($reqTime);

                $completed = $gponDone || $snmpDone;
                if ($gponDone) {
                    $lastCompleted = $latestGpon->completed_at;
                } elseif ($snmpDone) {
                    $lastCompleted = $latestSnmp->completed_at;
                }
            } catch (\Exception $e) {
                Log::warning('getOltSyncStatus error: ' . $e->getMessage());
            }
        }

        $onlineCount  = DB::table('onus')->where('status', 'online')->count();
        $offlineCount = DB::table('onus')->where('status', 'offline')->count();

        return response()->json([
            'completed'      => $completed,
            'last_completed' => $lastCompleted,
            'online_count'   => $onlineCount,
            'offline_count'  => $offlineCount,
            'timestamp'      => now()->format('H:i:s'),
        ]);
    }


}
