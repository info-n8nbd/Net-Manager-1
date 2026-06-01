<?php
/**
 * Live Monitor Controller
 * 
 * Provides real-time monitoring endpoints for:
 * - Bandwidth usage (live traffic from MikroTik)
 * - Ping status (ICMP ping to client IP)
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Services\SimpleMikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveMonitorController extends Controller
{
    /**
     * Get live bandwidth data for a client
     * Returns current RX/TX speeds from active session
     */
    protected function connectForClient(Client $client, int $timeout = 5): ?SimpleMikroTikService
    {
        $mikrotik = new SimpleMikroTikService();
        $triedIds = [];

        // 1. Try client's personally assigned device
        if ($client->mikrotik_device_id) {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if ($device && $device->is_active) {
                $triedIds[] = $device->id;
                if ($mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728), $timeout)) {
                    return $mikrotik;
                }
            }
        }

        // 2. Try device assigned to client's zone
        if ($client->zone_id) {
            $device = MikrotikDevice::where('zone_id', $client->zone_id)->where('is_active', true)->first();
            if ($device && !in_array($device->id, $triedIds)) {
                $triedIds[] = $device->id;
                if ($mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728), $timeout)) {
                    return $mikrotik;
                }
            }
        }

        // 3. Try ALL remaining active devices (fallback — covers zone mismatches)
        $allDevices = MikrotikDevice::where('is_active', true)
            ->whereNotIn('id', $triedIds)
            ->orderByRaw("CASE WHEN zone_id IS NULL THEN 1 ELSE 0 END") // prefer non-zone-specific devices last
            ->get();

        foreach ($allDevices as $device) {
            $host = $device->host;
            $port = (int)($device->getRawOriginal('port') ?? $device->port ?? 8728);
            if ($mikrotik->connect($host, $device->username, $device->password, $port, $timeout)) {
                return $mikrotik;
            }
        }

        return null;
    }

    public function getBandwidth(Client $client)
    {
        try {
            $mikrotik = $this->connectForClient($client, 2);
            
            if (!$mikrotik) {
                return response()->json([
                    'success' => false,
                    'no_router' => true,
                    'message' => 'Router Not Connected',
                    'timestamp' => now()->format('H:i:s'),
                    'data' => $this->getEmptyBandwidthData($client)
                ]);
            }

            $connectionType = $client->connection_type ?? 'pppoe';
            $username = $client->pppoe_username ?? $client->username;
            $ipAddress = $client->ip_address;

            $bandwidth = null;

            try {
                switch (strtolower($connectionType)) {
                    case 'pppoe':
                        $bandwidth = $this->getPPPoEBandwidth($mikrotik, $username, $client);
                        break;
                    case 'hotspot':
                        $bandwidth = $this->getHotspotBandwidth($mikrotik, $username, $client->id);
                        break;
                    case 'static':
                    case 'dhcp':
                        $bandwidth = $this->getQueueBandwidth($mikrotik, $username, $ipAddress, $client->id);
                        break;
                    default:
                        $bandwidth = $this->getPPPoEBandwidth($mikrotik, $username, $client);
                }
            } finally {
                try { $mikrotik->disconnect(); } catch (\Exception $ex) {}
            }

            return response()->json([
                'success' => true,
                'timestamp' => now()->format('H:i:s'),
                'data' => $bandwidth ?? $this->getEmptyBandwidthData($client)
            ]);

        } catch (\Exception $e) {
            Log::error("Live bandwidth error for client {$client->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'timestamp' => now()->format('H:i:s'),
                'data' => $this->getEmptyBandwidthData($client)
            ]);
        }
    }

    private function calculateRates(int $clientId, int $currentRxBytes, int $currentTxBytes): array
    {
        $cacheKey = "bw_prev_{$clientId}";
        $now = microtime(true);
        $prev = cache()->get($cacheKey);

        cache()->put($cacheKey, [
            'rx_bytes' => $currentRxBytes,
            'tx_bytes' => $currentTxBytes,
            'time' => $now,
        ], now()->addMinutes(2));

        if (!$prev || !isset($prev['time'])) {
            return ['rx_rate' => 0, 'tx_rate' => 0];
        }

        $elapsed = $now - $prev['time'];
        if ($elapsed < 0.5) {
            return ['rx_rate' => 0, 'tx_rate' => 0];
        }

        $rxDelta = max(0, $currentRxBytes - ($prev['rx_bytes'] ?? 0));
        $txDelta = max(0, $currentTxBytes - ($prev['tx_bytes'] ?? 0));

        $rxBitsPerSec = ($rxDelta * 8) / $elapsed;
        $txBitsPerSec = ($txDelta * 8) / $elapsed;

        return [
            'rx_rate' => round($rxBitsPerSec / 1000000, 2),
            'tx_rate' => round($txBitsPerSec / 1000000, 2),
        ];
    }

    /**
     * Get PPPoE session bandwidth
     */
    private function getPPPoEBandwidth(SimpleMikroTikService $mikrotik, string $username, Client $client): array
    {
        $session = $mikrotik->getPPPoESession($username);

        $lastDisconnect = null;
        $secret = $mikrotik->findPPPoESecret($username);
        if ($secret && !empty($secret['last-logged-out'])) {
            $rawDisconnect = $secret['last-logged-out'];
            try {
                $parsed = \Carbon\Carbon::parse($rawDisconnect);
                $lastDisconnect = $parsed->format('d-m-y h:i A');
                if ($client->last_logout !== $rawDisconnect) {
                    $client->update(['last_logout' => $rawDisconnect]);
                }
            } catch (\Exception $e) {
                $lastDisconnect = $rawDisconnect;
            }
        }

        if (!$session) {
            $empty = $this->getEmptyBandwidthData($client);
            $empty['last_disconnect'] = $lastDisconnect ?? $empty['last_disconnect'];
            return $empty;
        }

        // tx-bytes = interface TX = bytes sent to client = client download (rx from client's view)
        // rx-bytes = interface RX = bytes received from client = client upload (tx from client's view)
        // Fallback chain: interface bytes → session bytes-out/in → 0
        $rxBytes = $this->parseBytes($session['tx-bytes'] ?? $session['bytes-out'] ?? 0);
        $txBytes = $this->parseBytes($session['rx-bytes'] ?? $session['bytes-in']  ?? 0);

        $rates = $this->calculateRates($client->id, $rxBytes, $txBytes);

        $sessionIp = $session['address'] ?? null;
        if ($sessionIp && $client->session_ip !== $sessionIp) {
            $client->update(['session_ip' => $sessionIp]);
        }

        return [
            'online' => true,
            'rx_bytes' => $rxBytes,
            'tx_bytes' => $txBytes,
            'rx_rate' => $rates['rx_rate'],
            'tx_rate' => $rates['tx_rate'],
            'uptime' => $session['uptime'] ?? '0s',
            'ip_address' => $sessionIp ?? 'N/A',
            'caller_id' => $session['caller-id'] ?? 'N/A',
            'last_disconnect' => $lastDisconnect,
        ];
    }

    /**
     * Get Hotspot session bandwidth
     */
    private function getHotspotBandwidth(SimpleMikroTikService $mikrotik, string $username, int $clientId): array
    {
        $session = $mikrotik->getHotspotSession($username);
        
        if (!$session) {
            return $this->getEmptyBandwidthData();
        }

        $rxBytes = $this->parseBytes($session['bytes-in'] ?? 0);
        $txBytes = $this->parseBytes($session['bytes-out'] ?? 0);

        $rates = $this->calculateRates($clientId, $rxBytes, $txBytes);

        return [
            'online' => true,
            'rx_bytes' => $rxBytes,
            'tx_bytes' => $txBytes,
            'rx_rate' => $rates['rx_rate'],
            'tx_rate' => $rates['tx_rate'],
            'uptime' => $session['uptime'] ?? '0s',
            'ip_address' => $session['address'] ?? 'N/A',
            'mac_address' => $session['mac-address'] ?? 'N/A',
        ];
    }

    /**
     * Get Simple Queue bandwidth
     */
    private function getQueueBandwidth(SimpleMikroTikService $mikrotik, string $name, ?string $ip, int $clientId): array
    {
        $queue = $mikrotik->getSimpleQueueStats($name);
        
        if (!$queue && $ip) {
            $queue = $mikrotik->getSimpleQueueByTarget($ip);
        }
        
        if (!$queue) {
            return $this->getEmptyBandwidthData();
        }

        $rxBytes = $this->parseBytes($queue['bytes'] ?? '0/0', 'in');
        $txBytes = $this->parseBytes($queue['bytes'] ?? '0/0', 'out');

        $rates = $this->calculateRates($clientId, $rxBytes, $txBytes);

        return [
            'online' => !($queue['disabled'] ?? false),
            'rx_bytes' => $rxBytes,
            'tx_bytes' => $txBytes,
            'rx_rate' => $rates['rx_rate'],
            'tx_rate' => $rates['tx_rate'],
            'max_limit' => $queue['max-limit'] ?? 'N/A',
            'target' => $queue['target'] ?? 'N/A',
        ];
    }

    /**
     * Ping client IP address via MikroTik router
     */
    public function ping(Request $request, Client $client)
    {
        try {
            $source = $request->query('source', 'mikrotik');
            $count = (int) $request->query('count', 4);
            $count = min(max($count, 1), 50);

            $ipAddress = $client->session_ip ?: ($client->ip_address ?: null);

            if (empty($ipAddress) && $client->username) {
                $mikrotik = $this->connectForClient($client);
                if ($mikrotik) {
                    try {
                        $session = $mikrotik->getActiveSession($client->username);
                        if ($session && !empty($session['address'])) {
                            $ipAddress = $session['address'];
                            $client->update(['session_ip' => $ipAddress]);
                        }
                        $mikrotik->disconnect();
                    } catch (\Exception $e) {
                        try { $mikrotik->disconnect(); } catch (\Exception $ex) {}
                    }
                }
            }

            if (empty($ipAddress)) {
                return response()->json([
                    'success' => false,
                    'online' => false,
                    'message' => 'No IP address found for client',
                    'replies' => [],
                    'ip_address' => 'N/A',
                    'source' => $source,
                    'sent' => 0, 'received' => 0, 'lost' => 0, 'packet_loss' => 100,
                    'min' => null, 'max' => null, 'avg' => null,
                ]);
            }

            $pingResult = null;

            if ($source === 'mikrotik') {
                $mikrotik = $this->connectForClient($client);
                if ($mikrotik) {
                    try {
                        $pingResult = $this->executeMikroTikPing($mikrotik, $ipAddress, $count);
                        $pingResult['source'] = 'mikrotik';
                        $mikrotik->disconnect();
                    } catch (\Exception $e) {
                        try { $mikrotik->disconnect(); } catch (\Exception $ex) {}
                        Log::debug("MikroTik ping failed: " . $e->getMessage());
                    }
                }
                if (!$pingResult) {
                    return response()->json([
                        'success' => false,
                        'online' => false,
                        'message' => 'Cannot connect to MikroTik router',
                        'replies' => [],
                        'ip_address' => $ipAddress,
                        'source' => 'mikrotik',
                        'sent' => 0, 'received' => 0, 'lost' => 0, 'packet_loss' => 100,
                        'min' => null, 'max' => null, 'avg' => null,
                    ]);
                }
            } else {
                $pingResult = $this->executeDirectPing($ipAddress, $count);
                $pingResult['source'] = 'client';
            }
            
            return response()->json([
                'success' => true,
                'online' => $pingResult['online'],
                'ip_address' => $ipAddress,
                'replies' => $pingResult['replies'] ?? [],
                'sent' => $pingResult['sent'] ?? $count,
                'received' => $pingResult['received'] ?? 0,
                'lost' => $pingResult['lost'] ?? $count,
                'packet_loss' => $pingResult['packet_loss'],
                'min' => $pingResult['min'] ?? null,
                'max' => $pingResult['max'] ?? null,
                'avg' => $pingResult['avg'] ?? null,
                'message' => $pingResult['message'] ?? null,
                'source' => $pingResult['source'] ?? $source,
                'method' => $pingResult['method'] ?? null,
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            Log::error("Ping error for client {$client->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'online' => false,
                'message' => $e->getMessage(),
                'replies' => [],
                'sent' => 0, 'received' => 0, 'lost' => 0, 'packet_loss' => 100,
                'min' => null, 'max' => null, 'avg' => null,
            ]);
        }
    }

    public function pingStream(Request $request, Client $client)
    {
        $source = $request->query('source', 'client');
        $count = min(max((int) $request->query('count', 20), 1), 50);
        $ipAddress = $client->session_ip ?: ($client->ip_address ?: null);

        // For MikroTik-side ping: always try to get the live session IP
        // (database IP may be stale; the active PPPoE session has the real current IP)
        $username = $client->pppoe_username ?? $client->username;
        if ($source === 'mikrotik' && $username) {
            $mikrotikIp = $this->connectForClient($client, 4);
            if ($mikrotikIp) {
                try {
                    $session = $mikrotikIp->getActiveSession($username);
                    if ($session && !empty($session['address'])) {
                        $liveIp = $session['address'];
                        $ipAddress = $liveIp;
                        if ($client->session_ip !== $liveIp) {
                            $client->update(['session_ip' => $liveIp]);
                        }
                    }
                    $mikrotikIp->disconnect();
                } catch (\Exception $e) {
                    try { $mikrotikIp->disconnect(); } catch (\Exception $ex) {}
                }
            }
        }

        // Fallback: if still empty, try the older approach
        if (empty($ipAddress) && $username) {
            $mikrotik = $this->connectForClient($client);
            if ($mikrotik) {
                try {
                    $session = $mikrotik->getActiveSession($username);
                    if ($session && !empty($session['address'])) {
                        $ipAddress = $session['address'];
                        $client->update(['session_ip' => $ipAddress]);
                    }
                    $mikrotik->disconnect();
                } catch (\Exception $e) { try { $mikrotik->disconnect(); } catch (\Exception $ex) {} }
            }
        }

        return response()->stream(function () use ($source, $count, $ipAddress, $client) {
            $send = function ($event, $data) {
                echo "event: {$event}\n";
                echo "data: " . json_encode($data) . "\n\n";
                if (ob_get_level()) ob_flush();
                flush();
            };

            if ($source === 'client') {
                $pingTarget = '8.8.8.8';
                $send('start', ['ip' => $pingTarget, 'count' => $count, 'source' => 'client']);

                $mikrotik = $this->connectForClient($client);
                if ($mikrotik) {
                    try {
                        $result = $this->executeMikroTikPing($mikrotik, $pingTarget, $count);
                        $mikrotik->disconnect();
                        foreach ($result['replies'] as $reply) {
                            $send('reply', $reply);
                            usleep(50000);
                        }
                        $send('done', [
                            'sent' => $result['sent'], 'received' => $result['received'],
                            'lost' => $result['lost'], 'packet_loss' => $result['packet_loss'],
                            'min' => $result['min'], 'max' => $result['max'], 'avg' => $result['avg'],
                            'method' => 'mikrotik-api', 'ip' => $pingTarget,
                        ]);
                    } catch (\Exception $e) {
                        try { $mikrotik->disconnect(); } catch (\Exception $ex) {}
                        $send('error', ['message' => 'MikroTik ping failed: ' . $e->getMessage()]);
                    }
                    return;
                }
                $send('error', ['message' => 'Cannot connect to MikroTik router.']);
                return;
            }

            if (empty($ipAddress) || !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                $send('error', ['message' => empty($ipAddress) ? 'No IP address found' : 'Invalid IP address']);
                return;
            }

            $send('start', ['ip' => $ipAddress, 'count' => $count, 'source' => 'mikrotik']);

            $mikrotik = $this->connectForClient($client);
            if ($mikrotik) {
                try {
                    $result = $this->executeMikroTikPing($mikrotik, $ipAddress, $count);
                    $mikrotik->disconnect();
                    foreach ($result['replies'] as $reply) {
                        $send('reply', $reply);
                        usleep(50000);
                    }
                    $send('done', [
                        'sent' => $result['sent'], 'received' => $result['received'],
                        'lost' => $result['lost'], 'packet_loss' => $result['packet_loss'],
                        'min' => $result['min'], 'max' => $result['max'], 'avg' => $result['avg'],
                        'method' => 'mikrotik-api', 'ip' => $ipAddress,
                    ]);
                } catch (\Exception $e) {
                    try { $mikrotik->disconnect(); } catch (\Exception $ex) {}
                    $send('error', ['message' => 'MikroTik ping failed: ' . $e->getMessage()]);
                }
                return;
            }

            $send('error', ['message' => 'Cannot connect to MikroTik router.']);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function executeDirectPing(string $ip, int $count = 4): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['online' => false, 'replies' => [], 'sent' => 0, 'received' => 0, 'lost' => 0, 'packet_loss' => 100, 'min' => null, 'max' => null, 'avg' => null, 'message' => 'Invalid IP'];
        }

        $icmpResult = $this->tryIcmpPing($ip, $count);
        if ($icmpResult !== null) {
            return $icmpResult;
        }

        return $this->executeTcpPing($ip, $count);
    }

    private function tryIcmpPing(string $ip, int $count): ?array
    {
        $pingBin = null;
        $candidates = ['/usr/bin/ping', '/bin/ping', '/sbin/ping'];
        foreach ($candidates as $c) {
            if (file_exists($c)) { $pingBin = $c; break; }
        }
        if (!$pingBin) {
            $which = trim(shell_exec('which ping 2>/dev/null') ?? '');
            if ($which && file_exists($which)) $pingBin = $which;
        }
        if (!$pingBin) return null;

        try {
            $output = [];
            $returnVar = 0;
            exec("{$pingBin} -c {$count} -W 2 " . escapeshellarg($ip) . " 2>&1", $output, $returnVar);
            $outputStr = implode("\n", $output);

            if (str_contains($outputStr, 'Operation not permitted') ||
                str_contains($outputStr, 'cap_net_raw') ||
                str_contains($outputStr, 'not found') ||
                str_contains($outputStr, 'setuid')) {
                return null;
            }

            $replies = [];
            $seq = 0;
            foreach ($output as $line) {
                if (preg_match('/from\s+([\d.]+).*?ttl[=:](\d+).*?time[=<]([\d.]+)/i', $line, $m)) {
                    $seq++;
                    $replies[] = [
                        'seq' => $seq,
                        'ip' => $m[1],
                        'ttl' => (int)$m[2],
                        'time' => round((float)$m[3], 1),
                        'status' => 'reply',
                    ];
                } elseif (str_contains($line, 'Request timeout') || str_contains($line, 'no answer') || (str_contains($line, 'icmp_seq') && str_contains($line, 'timeout'))) {
                    $seq++;
                    $replies[] = ['seq' => $seq, 'status' => 'timeout'];
                }
            }

            $packetLoss = 100;
            if (preg_match('/(\d+)% packet loss/', $outputStr, $m)) {
                $packetLoss = (int)$m[1];
            }

            $latencies = array_column(array_filter($replies, fn($r) => $r['status'] === 'reply'), 'time');
            $sent = count($replies);
            $received = count($latencies);
            $lost = $sent - $received;

            return [
                'online' => $received > 0,
                'replies' => $replies,
                'sent' => $sent ?: $count,
                'received' => $received,
                'lost' => $lost ?: $count,
                'packet_loss' => $packetLoss,
                'min' => !empty($latencies) ? round(min($latencies), 1) : null,
                'max' => !empty($latencies) ? round(max($latencies), 1) : null,
                'avg' => !empty($latencies) ? round(array_sum($latencies) / count($latencies), 1) : null,
                'method' => 'icmp',
                'message' => $received > 0 ? null : 'Host unreachable',
            ];
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Operation not permitted') || str_contains($msg, 'cap_net_raw') || str_contains($msg, 'setuid')) {
                return null;
            }
            return ['online' => false, 'replies' => [], 'sent' => 0, 'received' => 0, 'lost' => 0, 'packet_loss' => 100, 'min' => null, 'max' => null, 'avg' => null, 'method' => 'icmp', 'message' => 'Ping failed: ' . $msg];
        }
    }

    private function executeTcpPing(string $ip, int $count = 4): array
    {
        $ports = [80, 443, 8291, 8728, 53, 22, 8080, 21];
        $maxCount = min($count, 30);
        $replies = [];
        $sent = 0;
        $received = 0;
        $workingPort = null;

        foreach ($ports as $port) {
            $start = microtime(true);
            $conn = @fsockopen($ip, $port, $errno, $errstr, 2);
            $elapsed = round((microtime(true) - $start) * 1000, 1);
            if ($conn) {
                fclose($conn);
                $workingPort = $port;
                $sent++;
                $received++;
                $replies[] = ['seq' => $sent, 'ip' => $ip, 'port' => $port, 'time' => $elapsed, 'status' => 'reply'];
                break;
            }
        }

        if ($workingPort === null) {
            $failReplies = [];
            for ($i = 1; $i <= $maxCount; $i++) {
                $failReplies[] = ['seq' => $i, 'status' => 'timeout'];
            }
            return [
                'online' => false, 'replies' => $failReplies,
                'sent' => $maxCount, 'received' => 0, 'lost' => $maxCount, 'packet_loss' => 100,
                'min' => null, 'max' => null, 'avg' => null, 'method' => 'tcp',
                'message' => 'Host unreachable - no open TCP ports found',
            ];
        }

        for ($i = 1; $i < $maxCount; $i++) {
            $sent++;
            $start = microtime(true);
            $conn = @fsockopen($ip, $workingPort, $errno, $errstr, 2);
            $elapsed = round((microtime(true) - $start) * 1000, 1);
            if ($conn) {
                fclose($conn);
                $received++;
                $replies[] = ['seq' => $sent, 'ip' => $ip, 'port' => $workingPort, 'time' => $elapsed, 'status' => 'reply'];
            } else {
                $replies[] = ['seq' => $sent, 'status' => 'timeout'];
            }
        }

        $latencies = array_column(array_filter($replies, fn($r) => $r['status'] === 'reply'), 'time');
        $lost = $sent - $received;
        $packetLoss = $sent > 0 ? round(($lost / $sent) * 100, 0) : 100;

        return [
            'online' => $received > 0,
            'replies' => $replies,
            'sent' => $sent, 'received' => $received, 'lost' => $lost,
            'packet_loss' => $packetLoss,
            'min' => !empty($latencies) ? round(min($latencies), 1) : null,
            'max' => !empty($latencies) ? round(max($latencies), 1) : null,
            'avg' => !empty($latencies) ? round(array_sum($latencies) / count($latencies), 1) : null,
            'method' => 'tcp', 'tcp_port' => $workingPort, 'message' => null,
        ];
    }

    private function executeMikroTikPing(SimpleMikroTikService $mikrotik, string $ip, int $count = 4): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['online' => false, 'replies' => [], 'sent' => 0, 'received' => 0, 'lost' => 0, 'packet_loss' => 100, 'min' => null, 'max' => null, 'avg' => null, 'message' => 'Invalid IP address'];
        }

        try {
            $response = $mikrotik->sendPingCommand($ip, $count);
            $results = $mikrotik->parseResponse($response);
            
            if (empty($results)) {
                return ['online' => false, 'replies' => [], 'sent' => $count, 'received' => 0, 'lost' => $count, 'packet_loss' => 100, 'min' => null, 'max' => null, 'avg' => null, 'message' => 'No ping response from router'];
            }

            $replies = [];
            $latencies = [];
            $seq = 0;
            $lastRow = end($results);
            $totalSent = (int)($lastRow['sent'] ?? $count);
            $totalReceived = (int)($lastRow['received'] ?? 0);
            $packetLoss = (int)($lastRow['packet-loss'] ?? 100);

            foreach ($results as $row) {
                if (isset($row['host'])) {
                    $seq++;
                    if (isset($row['time']) && !isset($row['status'])) {
                        $rtt = $this->parseMikroTikTime($row['time']);
                        $latencies[] = $rtt;
                        $replies[] = ['seq' => $seq, 'ip' => $row['host'] ?? $ip, 'time' => $rtt, 'ttl' => (int)($row['ttl'] ?? 64), 'status' => 'reply'];
                    } elseif (isset($row['status']) && $row['status'] === 'timeout') {
                        $replies[] = ['seq' => $seq, 'status' => 'timeout'];
                    }
                }
                if (isset($row['min-rtt'])) $minRtt = $this->parseMikroTikTime($row['min-rtt']);
                if (isset($row['max-rtt'])) $maxRtt = $this->parseMikroTikTime($row['max-rtt']);
                if (isset($row['avg-rtt'])) $avgRtt = $this->parseMikroTikTime($row['avg-rtt']);
            }

            $avg = isset($avgRtt) ? $avgRtt : (!empty($latencies) ? round(array_sum($latencies) / count($latencies), 1) : null);
            $min = isset($minRtt) ? $minRtt : (!empty($latencies) ? round(min($latencies), 1) : null);
            $max = isset($maxRtt) ? $maxRtt : (!empty($latencies) ? round(max($latencies), 1) : null);

            return [
                'online' => $totalReceived > 0,
                'replies' => $replies,
                'sent' => $totalSent, 'received' => $totalReceived, 'lost' => $totalSent - $totalReceived,
                'packet_loss' => $packetLoss,
                'min' => $min, 'max' => $max, 'avg' => $avg,
                'method' => 'mikrotik-api',
            ];
        } catch (\Exception $e) {
            Log::error("MikroTik ping error for {$ip}: " . $e->getMessage());
            return ['online' => false, 'replies' => [], 'sent' => 0, 'received' => 0, 'lost' => 0, 'packet_loss' => 100, 'min' => null, 'max' => null, 'avg' => null, 'message' => 'Router ping failed: ' . $e->getMessage()];
        }
    }

    /**
     * Parse MikroTik time format (e.g., "1ms", "500us", "1s500ms") to milliseconds
     */
    private function parseMikroTikTime(string $time): float
    {
        $ms = 0;
        if (preg_match('/([\d.]+)s(?!e)/', $time, $m)) {
            $ms += (float)$m[1] * 1000;
        }
        if (preg_match('/([\d.]+)ms/', $time, $m)) {
            $ms += (float)$m[1];
        }
        if (preg_match('/([\d.]+)us/', $time, $m)) {
            $ms += (float)$m[1] / 1000;
        }
        if ($ms == 0 && is_numeric($time)) {
            $ms = (float)$time;
        }
        return round($ms, 2);
    }

    /**
     * Get bandwidth history for charts (last N readings)
     */
    public function getBandwidthHistory(Client $client, Request $request)
    {
        $points = min((int)$request->get('points', 30), 60);
        
        // Get cached history or return empty
        $cacheKey = "bandwidth_history_{$client->id}";
        $history = cache()->get($cacheKey, []);

        $mikrotik = $this->connectForClient($client);
        if ($mikrotik) {
            $username = $client->pppoe_username ?? $client->username;
            $session = $mikrotik->getPPPoESession($username);
            
            if ($session) {
                $rxBytes = $this->parseBytes($session['tx-bytes'] ?? 0);
                $txBytes = $this->parseBytes($session['rx-bytes'] ?? 0);
                $rates = $this->calculateRates($client->id, $rxBytes, $txBytes);

                $history[] = [
                    'time' => now()->format('H:i:s'),
                    'rx' => $rates['rx_rate'],
                    'tx' => $rates['tx_rate'],
                ];
                
                $history = array_slice($history, -$points);
                cache()->put($cacheKey, $history, now()->addMinutes(5));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Parse bytes from MikroTik format
     */
    private function parseBytes($value, $direction = null): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        if (str_contains($value, '/')) {
            $parts = explode('/', $value);
            if ($direction === 'in') {
                return (int)($parts[0] ?? 0);
            } elseif ($direction === 'out') {
                return (int)($parts[1] ?? 0);
            }
        }

        return (int)$value;
    }

    /**
     * Parse rate from MikroTik format (bps)
     */
    private function parseRate($value): float
    {
        if (is_numeric($value)) {
            return round((float)$value / 1000000, 2); // Convert to Mbps
        }
        return 0;
    }

    /**
     * Parse queue rate (format: "in/out")
     */
    private function parseQueueRate($value, $direction): float
    {
        if (str_contains($value, '/')) {
            $parts = explode('/', $value);
            $rate = $direction === 'in' ? ($parts[0] ?? 0) : ($parts[1] ?? 0);
            return round((float)$rate / 1000000, 2);
        }
        return 0;
    }

    /**
     * Get empty bandwidth data structure
     */
    private function getEmptyBandwidthData(?Client $client = null): array
    {
        $lastDisconnect = null;
        if ($client && $client->last_logout) {
            try {
                $lastDisconnect = \Carbon\Carbon::parse($client->last_logout)->format('d-m-y h:i A');
            } catch (\Exception $e) {
                $lastDisconnect = $client->last_logout;
            }
        }

        return [
            'online' => false,
            'rx_bytes' => 0,
            'tx_bytes' => 0,
            'rx_rate' => 0,
            'tx_rate' => 0,
            'uptime' => '0s',
            'ip_address' => 'N/A',
            'last_disconnect' => $lastDisconnect,
        ];
    }
}
