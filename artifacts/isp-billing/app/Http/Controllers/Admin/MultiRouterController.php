<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikDevice;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MultiRouterController extends Controller
{
    protected int $cacheTtl = 60;

    public function index()
    {
        $devices = MikrotikDevice::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.network.multi-router', compact('devices'));
    }

    public function liveStatus()
    {
        $devices = MikrotikDevice::where('is_active', true)->get();
        $statuses = [];

        foreach ($devices as $device) {
            $cacheKey = "router_status:{$device->id}";
            $cached = Cache::get($cacheKey);

            if ($cached) {
                $statuses[$device->id] = $cached;
            } else {
                $statuses[$device->id] = [
                    'id' => $device->id,
                    'name' => $device->name,
                    'host' => $device->host,
                    'status' => 'unknown',
                    'active_users' => $device->active_users ?? 0,
                    'uptime' => null,
                    'cpu_load' => null,
                    'memory_usage' => null,
                    'last_sync' => $device->last_sync_at?->diffForHumans(),
                    'needs_sync' => true,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'devices' => array_values($statuses),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function syncDevice(Request $request, MikrotikDevice $device)
    {
        try {
            $mikrotik = new MikroTikService();
            
            $result = $mikrotik->connect($device);
            if ($result === false) {
                $failStatus = [
                    'id' => $device->id,
                    'name' => $device->name,
                    'host' => $device->host,
                    'status' => 'offline',
                    'active_users' => 0,
                    'error' => 'Failed to connect to router',
                    'last_sync' => $device->last_sync_at?->diffForHumans() ?? 'never',
                ];
                $device->update(['status' => 'offline']);
                Cache::put("router_status:{$device->id}", $failStatus, $this->cacheTtl);
                return response()->json([
                    'success' => false,
                    'device' => $failStatus,
                    'message' => 'Failed to connect to router',
                ]);
            }

            $resource = $mikrotik->getSystemResources();
            $activeConnections = $mikrotik->getActiveSessions();
            $interfaces = $mikrotik->getInterfaces();

            $mikrotik->disconnect();

            $status = [
                'id' => $device->id,
                'name' => $device->name,
                'host' => $device->host,
                'status' => 'online',
                'active_users' => count($activeConnections),
                'uptime' => $resource['uptime'] ?? null,
                'cpu_load' => $resource['cpu-load'] ?? null,
                'memory_usage' => isset($resource['free-memory'], $resource['total-memory']) 
                    ? round((1 - ($resource['free-memory'] / $resource['total-memory'])) * 100, 1) 
                    : null,
                'version' => $resource['version'] ?? null,
                'board_name' => $resource['board-name'] ?? null,
                'total_interfaces' => count($interfaces),
                'last_sync' => 'just now',
                'synced_at' => now()->toIso8601String(),
            ];

            $device->update([
                'last_sync_at' => now(),
                'last_sync_data' => $status,
                'active_users' => count($activeConnections),
                'status' => 'online',
            ]);

            Cache::put("router_status:{$device->id}", $status, $this->cacheTtl);

            return response()->json([
                'success' => true,
                'device' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error("Router sync failed: " . $e->getMessage(), [
                'device_id' => $device->id,
                'host' => $device->host,
            ]);

            $status = [
                'id' => $device->id,
                'name' => $device->name,
                'host' => $device->host,
                'status' => 'offline',
                'active_users' => 0,
                'error' => $e->getMessage(),
                'last_sync' => $device->last_sync_at?->diffForHumans() ?? 'never',
            ];

            $device->update(['status' => 'offline']);

            Cache::put("router_status:{$device->id}", $status, $this->cacheTtl);

            return response()->json([
                'success' => false,
                'device' => $status,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function syncAll(Request $request)
    {
        $devices = MikrotikDevice::where('is_active', true)->get();
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($devices as $device) {
            try {
                $mikrotik = new MikroTikService();
                
                $connectResult = $mikrotik->connect($device);
                if ($connectResult !== false) {
                    $resource = $mikrotik->getSystemResources();
                    $activeConnections = $mikrotik->getActiveSessions();
                    $mikrotik->disconnect();

                    $status = [
                        'id' => $device->id,
                        'name' => $device->name,
                        'host' => $device->host,
                        'status' => 'online',
                        'active_users' => count($activeConnections),
                        'cpu_load' => $resource['cpu-load'] ?? null,
                        'memory_usage' => isset($resource['free-memory'], $resource['total-memory']) 
                            ? round((1 - ($resource['free-memory'] / $resource['total-memory'])) * 100, 1) 
                            : null,
                        'uptime' => $resource['uptime'] ?? null,
                        'last_sync' => 'just now',
                    ];

                    $device->update([
                        'last_sync_at' => now(),
                        'last_sync_data' => $status,
                        'active_users' => count($activeConnections),
                        'status' => 'online',
                    ]);

                    Cache::put("router_status:{$device->id}", $status, $this->cacheTtl);
                    $results[$device->id] = $status;
                    $successCount++;
                } else {
                    $failStatus = [
                        'id' => $device->id,
                        'name' => $device->name,
                        'host' => $device->host,
                        'status' => 'offline',
                        'active_users' => 0,
                        'last_sync' => $device->last_sync_at?->diffForHumans() ?? 'never',
                    ];
                    $results[$device->id] = $failStatus;
                    $device->update(['status' => 'offline']);
                    Cache::put("router_status:{$device->id}", $failStatus, $this->cacheTtl);
                    $failCount++;
                }
            } catch (\Exception $e) {
                $errorStatus = [
                    'id' => $device->id,
                    'name' => $device->name,
                    'host' => $device->host,
                    'status' => 'offline',
                    'active_users' => 0,
                    'error' => $e->getMessage(),
                    'last_sync' => $device->last_sync_at?->diffForHumans() ?? 'never',
                ];
                $results[$device->id] = $errorStatus;
                $device->update(['status' => 'offline']);
                Cache::put("router_status:{$device->id}", $errorStatus, $this->cacheTtl);
                $failCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Synced {$successCount} routers, {$failCount} failed",
            'results' => $results,
        ]);
    }

    public function getActiveUsers(MikrotikDevice $device)
    {
        $cacheKey = "router_users:{$device->id}";
        
        $users = Cache::remember($cacheKey, 30, function () use ($device) {
            try {
                $mikrotik = new MikroTikService();
                
                $connectResult = $mikrotik->connect($device);
                if ($connectResult === false) {
                    return [];
                }

                $connections = $mikrotik->getActiveSessions();
                $mikrotik->disconnect();

                return collect($connections)->map(function ($conn) {
                    return [
                        'name' => $conn['name'] ?? 'unknown',
                        'address' => $conn['address'] ?? '',
                        'uptime' => $conn['uptime'] ?? '',
                        'caller_id' => $conn['caller-id'] ?? '',
                    ];
                })->take(100)->toArray();

            } catch (\Exception $e) {
                Log::warning("Failed to get active users: " . $e->getMessage());
                return [];
            }
        });

        return response()->json([
            'success' => true,
            'device_id' => $device->id,
            'device_name' => $device->name,
            'users' => $users,
            'count' => count($users),
        ]);
    }

    public function getBandwidthStats()
    {
        $devices = MikrotikDevice::where('is_active', true)->get();
        
        $stats = [
            'total_active_users' => $devices->sum('active_users'),
            'total_devices' => $devices->count(),
            'online_devices' => $devices->where('status', 'online')->count(),
            'offline_devices' => $devices->where('status', '!=', 'online')->count(),
            'devices' => $devices->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->name,
                    'active_users' => $d->active_users,
                    'status' => $d->status ?? 'unknown',
                    'last_sync' => $d->last_sync_at?->diffForHumans(),
                ];
            }),
        ];

        return response()->json($stats);
    }
}
