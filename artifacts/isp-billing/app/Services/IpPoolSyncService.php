<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IpPoolSyncService
{
    public function syncPoolToRouter(int $poolId): array
    {
        try {
            $pool = DB::table('ip_pools')->where('id', $poolId)->first();
            if (!$pool) {
                return ['success' => false, 'message' => 'Pool not found', 'data' => []];
            }

            $device = MikrotikDevice::find($pool->mikrotik_device_id);
            if (!$device) {
                return ['success' => false, 'message' => 'No router linked to this pool', 'data' => []];
            }

            $mikrotik = new SimpleMikroTikService();
            $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

            if (!$connected) {
                return ['success' => false, 'message' => 'Failed to connect to router', 'data' => []];
            }

            $existingPools = $mikrotik->sendCommand('/ip/pool/print', ['?name' => $pool->name]);
            $ranges = $pool->range_start . '-' . $pool->range_end;

            if (is_array($existingPools) && count($existingPools) > 0 && isset($existingPools[0]['.id'])) {
                $mikrotik->sendCommand('/ip/pool/set', [
                    '.id' => $existingPools[0]['.id'],
                    'ranges' => $ranges,
                ]);
            } else {
                $params = [
                    'name' => $pool->name,
                    'ranges' => $ranges,
                ];
                if (!empty($pool->next_pool)) {
                    $params['next-pool'] = $pool->next_pool;
                }
                $mikrotik->sendCommand('/ip/pool/add', $params);
            }

            DB::table('ip_pools')->where('id', $poolId)->update([
                'synced_to_router' => true,
                'last_synced_at' => now(),
                'updated_at' => now(),
            ]);

            return ['success' => true, 'message' => "Pool '{$pool->name}' synced to router.", 'data' => []];
        } catch (\Exception $e) {
            Log::error('IP Pool sync to router error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Sync failed: ' . $e->getMessage(), 'data' => []];
        }
    }

    public function syncPoolFromRouter(MikrotikDevice $device): array
    {
        try {
            $mikrotik = new SimpleMikroTikService();
            $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

            if (!$connected) {
                return ['success' => false, 'message' => 'Failed to connect to router', 'data' => []];
            }

            $response = $mikrotik->sendCommand('/ip/pool/print', []);
            $pools = is_array($response) ? $response : [];
            $imported = 0;

            foreach ($pools as $pool) {
                if (!is_array($pool) || isset($pool['!trap'])) continue;

                $name = $pool['name'] ?? '';
                if (empty($name)) continue;

                $ranges = $pool['ranges'] ?? '';
                $rangeParts = explode('-', $ranges, 2);
                $rangeStart = $rangeParts[0] ?? '';
                $rangeEnd = $rangeParts[1] ?? $rangeStart;

                $existing = DB::table('ip_pools')
                    ->where('name', $name)
                    ->where('mikrotik_device_id', $device->id)
                    ->first();

                if ($existing) {
                    DB::table('ip_pools')->where('id', $existing->id)->update([
                        'range_start' => $rangeStart,
                        'range_end' => $rangeEnd,
                        'next_pool' => $pool['next-pool'] ?? null,
                        'synced_to_router' => true,
                        'last_synced_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('ip_pools')->insert([
                        'name' => $name,
                        'range_start' => $rangeStart,
                        'range_end' => $rangeEnd,
                        'next_pool' => $pool['next-pool'] ?? null,
                        'mikrotik_device_id' => $device->id,
                        'synced_to_router' => true,
                        'last_synced_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $imported++;
            }

            return [
                'success' => true,
                'message' => "Imported {$imported} pools from router.",
                'data' => ['count' => $imported],
            ];
        } catch (\Exception $e) {
            Log::error('IP Pool sync from router error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Import failed: ' . $e->getMessage(), 'data' => []];
        }
    }

    public function getRouterPools(MikrotikDevice $device): array
    {
        try {
            $mikrotik = new SimpleMikroTikService();
            $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

            if (!$connected) {
                return [];
            }

            $response = $mikrotik->sendCommand('/ip/pool/print', []);
            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            Log::error('Get router pools error: ' . $e->getMessage());
            return [];
        }
    }

    public function assignIpToClient(int $poolId, int $clientId): array
    {
        try {
            $pool = DB::table('ip_pools')->where('id', $poolId)->first();
            if (!$pool) {
                return ['success' => false, 'message' => 'Pool not found', 'data' => []];
            }

            $client = Client::find($clientId);
            if (!$client) {
                return ['success' => false, 'message' => 'Client not found', 'data' => []];
            }

            $nextIp = DB::table('ip_addresses')
                ->where('ip_pool_id', $poolId)
                ->where('status', 'available')
                ->orderBy('ip_address')
                ->first();

            if (!$nextIp) {
                return ['success' => false, 'message' => 'No available IPs in this pool', 'data' => []];
            }

            DB::table('ip_addresses')->where('id', $nextIp->id)->update([
                'status' => 'assigned',
                'client_id' => $clientId,
                'assigned_at' => now(),
                'updated_at' => now(),
            ]);

            $client->update(['ip_address' => $nextIp->ip_address]);

            return [
                'success' => true,
                'message' => "IP {$nextIp->ip_address} assigned to client.",
                'data' => ['ip_address' => $nextIp->ip_address],
            ];
        } catch (\Exception $e) {
            Log::error('IP assign error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Assignment failed: ' . $e->getMessage(), 'data' => []];
        }
    }

    public function releaseClientIp(int $clientId): array
    {
        try {
            $client = Client::find($clientId);
            if (!$client) {
                return ['success' => false, 'message' => 'Client not found', 'data' => []];
            }

            $ipRecord = DB::table('ip_addresses')
                ->where('client_id', $clientId)
                ->where('status', 'assigned')
                ->first();

            if ($ipRecord) {
                DB::table('ip_addresses')->where('id', $ipRecord->id)->update([
                    'status' => 'available',
                    'client_id' => null,
                    'assigned_at' => null,
                    'updated_at' => now(),
                ]);
            }

            $client->update(['ip_address' => null]);

            return [
                'success' => true,
                'message' => 'IP released successfully.',
                'data' => [],
            ];
        } catch (\Exception $e) {
            Log::error('IP release error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Release failed: ' . $e->getMessage(), 'data' => []];
        }
    }
}
