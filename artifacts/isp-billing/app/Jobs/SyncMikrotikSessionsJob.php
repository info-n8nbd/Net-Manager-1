<?php

namespace App\Jobs;

use App\Models\MikrotikDevice;
use App\Models\Client;
use App\Services\MikroTikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DeviceCircuitBreaker;
use App\Services\TenantContext;

class SyncMikrotikSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $devices = MikrotikDevice::where('is_active', true)->get();
            $allSecretsMap = [];

            foreach ($devices as $device) {
                try {
                    $service = new MikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728)));
                    
                    if (!$service->connect()) {
                        $this->logNetworkEvent('router_down', $device, null, "Cannot connect to device: {$device->name}");
                        continue;
                    }

                    $activeSessions = $service->getPPPoEActiveUsers();

                    $secretsMap = [];
                    try {
                        $secrets = $service->getAllPPPoESecrets();
                        foreach ($secrets as $secret) {
                            $sName = $secret['name'] ?? '';
                            if ($sName && !empty($secret['last-logged-out'])) {
                                $secretsMap[$sName] = $secret['last-logged-out'];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::debug("Could not fetch PPP secrets: " . $e->getMessage());
                    }
                    $allSecretsMap = array_merge($allSecretsMap, $secretsMap);

                    DB::table('mikrotik_sessions')
                        ->where('mikrotik_device_id', $device->id)
                        ->update(['status' => 'offline']);

                    foreach ($activeSessions as $session) {
                        $username = $session['name'] ?? '';
                        if (empty($username)) continue;

                        $client = Client::where('username', $username)->first();

                        DB::table('mikrotik_sessions')->updateOrInsert(
                            [
                                'mikrotik_device_id' => $device->id,
                                'username' => $username,
                            ],
                            [
                                'client_id' => $client?->id,
                                'service' => $session['service'] ?? 'pppoe',
                                'caller_id' => $session['caller-id'] ?? null,
                                'address' => $session['address'] ?? null,
                                'uptime_seconds' => $this->parseUptime($session['uptime'] ?? ''),
                                'bytes_in' => intval($session['bytes-in'] ?? 0),
                                'bytes_out' => intval($session['bytes-out'] ?? 0),
                                'rate_in' => $session['rate-in'] ?? null,
                                'rate_out' => $session['rate-out'] ?? null,
                                'status' => 'online',
                                'last_seen' => now(),
                                'updated_at' => now(),
                            ]
                        );

                        if ($client) {
                            $client->update([
                                'connection_status' => 'online',
                                'last_online' => now(),
                                'session_ip' => $session['address'] ?? null,
                                'session_mac' => $session['caller-id'] ?? null,
                                'session_uptime' => $session['uptime'] ?? null,
                            ]);
                        }

                        $recordsProcessed++;
                    }

                    $resources = $service->getSystemResources();
                    if (!empty($resources)) {
                        DB::table('mikrotik_router_stats')->updateOrInsert(
                            ['mikrotik_device_id' => $device->id],
                            [
                                'cpu_load' => intval($resources['cpu-load'] ?? 0),
                                'free_memory' => intval($resources['free-memory'] ?? 0),
                                'total_memory' => intval($resources['total-memory'] ?? 0),
                                'free_hdd' => intval($resources['free-hdd-space'] ?? 0),
                                'total_hdd' => intval($resources['total-hdd-space'] ?? 0),
                                'uptime' => $resources['uptime'] ?? null,
                                'version' => $resources['version'] ?? null,
                                'board_name' => $resources['board-name'] ?? null,
                                'updated_at' => now(),
                            ]
                        );
                    }

                    DeviceCircuitBreaker::onSuccess('mikrotik', $device->id, $device->name);
                    $device->update(['last_connected_at' => now()]);
                    $service->disconnect();

                } catch (\Exception $e) {
                    DeviceCircuitBreaker::onFailure('mikrotik', $device->id, $device->name, $e->getMessage());
                    $this->logNetworkEvent('router_down', $device, null, $e->getMessage(), 'error');
                }
            }

            $onlineClientIds = DB::table('mikrotik_sessions')
                ->where('status', 'online')
                ->pluck('client_id')
                ->filter();

            $offlineClients = Client::whereNotIn('id', $onlineClientIds)
                ->where('connection_status', 'online')
                ->get(['id', 'username']);

            foreach ($offlineClients as $offClient) {
                $logoutTime = now();
                if (isset($allSecretsMap[$offClient->username])) {
                    try {
                        $logoutTime = \Carbon\Carbon::parse($allSecretsMap[$offClient->username]);
                    } catch (\Exception $e) {
                        $logoutTime = now();
                    }
                }
                $offClient->update([
                    'connection_status' => 'offline',
                    'session_ip' => null,
                    'session_mac' => null,
                    'session_uptime' => null,
                    'last_logout' => $logoutTime,
                ]);
            }

            if (!empty($allSecretsMap)) {
                $missingLogout = Client::whereNull('last_logout')
                    ->whereIn('username', array_keys($allSecretsMap))
                    ->get(['id', 'username']);
                foreach ($missingLogout as $ml) {
                    try {
                        $ml->update(['last_logout' => \Carbon\Carbon::parse($allSecretsMap[$ml->username])]);
                    } catch (\Exception $e) {}
                }
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            $this->logCronFailed($startTime, $e->getMessage());
            throw $e;
        }
    }

    protected function parseUptime(string $uptime): int
    {
        $seconds = 0;
        if (preg_match('/(\d+)w/', $uptime, $m)) $seconds += intval($m[1]) * 7 * 24 * 3600;
        if (preg_match('/(\d+)d/', $uptime, $m)) $seconds += intval($m[1]) * 24 * 3600;
        if (preg_match('/(\d+)h/', $uptime, $m)) $seconds += intval($m[1]) * 3600;
        if (preg_match('/(\d+)m/', $uptime, $m)) $seconds += intval($m[1]) * 60;
        if (preg_match('/(\d+)s/', $uptime, $m)) $seconds += intval($m[1]);
        return $seconds;
    }

    protected function logNetworkEvent(string $type, $device, $client, string $message, string $severity = 'warning'): void
    {
        try {
            DB::table('network_event_logs')->insert([
                'event_type' => $type,
                'mikrotik_device_id' => $device?->id,
                'description' => $message,
                'severity' => $severity,
                'affected_count' => 0,
                'is_resolved' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log network event: ' . $e->getMessage());
        }
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'sync_mikrotik_sessions',
            'job_class' => self::class,
            'status' => 'started',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function logCronComplete(float $startTime, int $records): void
    {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        DB::table('cron_job_logs')
            ->where('job_class', self::class)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'status' => 'completed',
                'records_processed' => $records,
                'execution_time_ms' => $executionTime,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function logCronFailed(float $startTime, string $message): void
    {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        DB::table('cron_job_logs')
            ->where('job_class', self::class)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'status' => 'failed',
                'message' => $message,
                'execution_time_ms' => $executionTime,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
