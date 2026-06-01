<?php

namespace App\Services;

use App\Models\OltDevice;
use App\Services\OLT\OIDMapping;
use App\Services\OLT\SNMPClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DeviceCircuitBreaker;

class OltRealtimeService
{
    public function pollAllOlts(): array
    {
        $results = ['polled' => 0, 'onus_updated' => 0, 'errors' => []];
        $olts = OltDevice::where('is_active', true)->get();

        foreach ($olts as $olt) {
            try {
                $onuData = $this->pollOltOnus($olt);
                $results['onus_updated'] += $this->updateOnuRecords($olt, $onuData);
                $results['polled']++;
            } catch (\Exception $e) {
                $results['errors'][] = "OLT {$olt->name}: " . $e->getMessage();
                DeviceCircuitBreaker::onFailure('olt', $olt->id, $olt->name, $e->getMessage());
            }
        }

        return $results;
    }

    public function pollOltOnus(OltDevice $olt): array
    {
        $onus = [];

        try {
            if (!empty($olt->ip_address)) {
                $oidMapping = OIDMapping::forVendor($olt->vendor ?? 'generic', $olt->pon_type ?? 'epon');
                $statusOid = $oidMapping->getOnuStatusOid();

                if ($statusOid) {
                    $snmpClient = SNMPClient::fromDevice($olt);
                    $snmpData = $snmpClient->walk($statusOid);

                    if (!empty($snmpData)) {
                        $rxOid = $oidMapping->getOnuRxPowerOid();
                        $txOid = $oidMapping->getOnuTxPowerOid();
                        $rxResults = $rxOid ? $snmpClient->walk($rxOid) : [];
                        $txResults = $txOid ? $snmpClient->walk($txOid) : [];

                        foreach ($snmpData as $oid => $rawStatus) {
                            $idx = $oidMapping->parseOnuIndex($oid);
                            $status = method_exists($oidMapping, 'parseOnuStatus')
                                ? $oidMapping->parseOnuStatus($rawStatus)
                                : ($rawStatus == 1 ? 'online' : 'offline');

                            $suffix = $this->extractSuffix($oid, $statusOid);

                            $rxRaw = $this->findMatchingValue($rxResults, $rxOid, $suffix);
                            $txRaw = $this->findMatchingValue($txResults, $txOid, $suffix);

                            $rxPower = $oidMapping->parseRxPower($rxRaw);
                            $txPower = $oidMapping->parseTxPower($txRaw);

                            $intf = $oidMapping->formatOnuInterface($idx['port_index'], $idx['onu_index']);

                            $portName = $oidMapping->formatPortName($idx['port_index']);
                            $storedOnu = DB::table('onus')
                                ->where('olt_device_id', $olt->id)
                                ->where('onu_number', $idx['onu_index'])
                                ->where(function ($q) use ($intf, $portName) {
                                    $q->where('pon_port', $intf)
                                      ->orWhere('pon_port', $portName);
                                })
                                ->first();

                            $onus[] = [
                                'serial' => $storedOnu->serial_number ?? null,
                                'port' => $intf,
                                'status' => $status,
                                'rx_power' => $rxPower,
                                'tx_power' => $txPower,
                                'uptime' => $storedOnu->uptime_seconds ?? null,
                            ];
                        }

                        Log::info("OLT {$olt->name}: Polled " . count($onus) . " ONUs via SNMP");
                    }
                }
            }
            
            if (empty($onus)) {
                $storedOnus = DB::table('onus')
                    ->where('olt_device_id', $olt->id)
                    ->get();

                foreach ($storedOnus as $onu) {
                    $onus[] = [
                        'serial' => $onu->serial_number,
                        'port' => $onu->pon_port,
                        'status' => $onu->status ?? 'unknown',
                        'rx_power' => $onu->rx_power ?? null,
                        'tx_power' => $onu->tx_power ?? null,
                        'uptime' => $onu->uptime_seconds ?? null,
                    ];
                }
                
                Log::info("OLT {$olt->name}: SNMP unavailable, using stored data for " . count($onus) . " ONUs");
            }

        } catch (\Exception $e) {
            Log::error("SNMP polling failed for OLT {$olt->name}: " . $e->getMessage());
            return $this->getFallbackOnuData($olt->id);
        }

        return $onus;
    }
    
    protected function getFallbackOnuData(int $oltId): array
    {
        return DB::table('onus')
            ->where('olt_device_id', $oltId)
            ->get()
            ->map(fn($onu) => [
                'serial' => $onu->serial_number,
                'port' => $onu->pon_port,
                'status' => $onu->status ?? 'unknown',
                'rx_power' => $onu->rx_power ?? null,
                'tx_power' => $onu->tx_power ?? null,
                'uptime' => $onu->uptime_seconds ?? null,
            ])
            ->toArray();
    }

    protected function updateOnuRecords(OltDevice $olt, array $onuData): int
    {
        $updated = 0;

        foreach ($onuData as $data) {
            if (empty($data['serial'])) continue;

            $onu = DB::table('onus')
                ->where('olt_device_id', $olt->id)
                ->where('serial_number', $data['serial'])
                ->first();

            if ($onu) {
                $newStatus = $this->determineOnuStatus($data);
                $statusChanged = $onu->status !== $newStatus;

                $updateData = [
                    'status' => $newStatus,
                    'last_seen' => $newStatus === 'online' ? now() : $onu->last_seen,
                    'last_down_at' => $newStatus === 'offline' && $onu->status === 'online' ? now() : ($onu->last_down_at ?? null),
                    'updated_at' => now(),
                ];

                if ($data['rx_power'] !== null) {
                    $updateData['rx_power'] = $data['rx_power'];
                }
                if ($data['tx_power'] !== null) {
                    $updateData['tx_power'] = $data['tx_power'];
                }
                if ($data['uptime'] !== null) {
                    $updateData['uptime_seconds'] = $data['uptime'];
                }

                DB::table('onus')
                    ->where('id', $onu->id)
                    ->update($updateData);

                if ($statusChanged) {
                    $this->logOnuEvent($onu->id, $newStatus === 'online' ? 'ONLINE' : 'OFFLINE', $data);
                    $updated++;
                }

                if ($data['rx_power'] !== null && $data['rx_power'] < -28) {
                    $this->createLowSignalAlert($onu->id, $data['rx_power']);
                }
            }
        }

        return $updated;
    }

    protected function determineOnuStatus(array $data): string
    {
        if (isset($data['status']) && in_array($data['status'], ['online', 'offline'])) {
            return $data['status'];
        }
        
        if (($data['rx_power'] ?? null) !== null && $data['rx_power'] < -32) {
            return 'offline';
        }

        return 'unknown';
    }

    protected function logOnuEvent(int $onuId, string $eventType, array $data): void
    {
        $rxInfo = ($data['rx_power'] !== null) ? " RX: {$data['rx_power']}dBm" : '';
        DB::table('onu_events')->insert([
            'onu_device_id' => $onuId,
            'event_type' => $eventType,
            'message' => "ONU status changed to {$eventType}.{$rxInfo}",
            'detected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createLowSignalAlert(int $onuId, float $rxPower): void
    {
        $existingAlert = DB::table('network_event_logs')
            ->where('event_type', 'low_signal')
            ->where('source', "onu_{$onuId}")
            ->where('is_resolved', false)
            ->exists();

        if (!$existingAlert) {
            $onu = DB::table('onus')->find($onuId);
            
            DB::table('network_event_logs')->insert([
                'event_type' => 'low_signal',
                'source' => "onu_{$onuId}",
                'client_id' => $onu?->client_id,
                'message' => "Low signal detected on ONU {$onu?->serial_number}: {$rxPower}dBm",
                'severity' => $rxPower < -30 ? 'critical' : 'warning',
                'metadata' => json_encode(['rx_power' => $rxPower, 'onu_id' => $onuId]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function getOnuRealtimeStatus(int $onuId): array
    {
        $onu = DB::table('onus')->find($onuId);
        if (!$onu) {
            return ['error' => 'ONU not found'];
        }

        $client = $onu->client_id ? DB::table('clients')->find($onu->client_id) : null;

        $recentEvents = DB::table('onu_events')
            ->where('onu_device_id', $onuId)
            ->orderByDesc('detected_at')
            ->limit(10)
            ->get();

        return [
            'onu_id' => $onuId,
            'serial' => $onu->serial_number,
            'port' => $onu->pon_port,
            'status' => $onu->status,
            'rx_power' => $onu->rx_power,
            'tx_power' => $onu->tx_power,
            'signal_quality' => $this->getSignalQuality($onu->rx_power),
            'uptime_hours' => round(($onu->uptime_seconds ?? 0) / 3600, 1),
            'last_seen' => $onu->last_seen ?? null,
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'client_id' => $client->client_id,
            ] : null,
            'recent_events' => $recentEvents,
        ];
    }

    protected function getSignalQuality(?float $rxPower): string
    {
        if ($rxPower === null) return 'unknown';
        if ($rxPower >= -20) return 'excellent';
        if ($rxPower >= -24) return 'good';
        if ($rxPower >= -28) return 'fair';
        return 'poor';
    }

    public function detectFiberCuts(): array
    {
        $cuts = [];

        $olts = OltDevice::where('is_active', true)->get();

        foreach ($olts as $olt) {
            $ports = DB::table('onus')
                ->where('olt_device_id', $olt->id)
                ->selectRaw('pon_port, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as offline_count', ['offline'])
                ->groupBy('pon_port')
                ->havingRaw('COUNT(*) >= 2')
                ->get();

            foreach ($ports as $port) {
                $offlinePercent = ($port->offline_count / $port->total) * 100;

                if ($offlinePercent >= 80 && $port->offline_count >= 3) {
                    $cuts[] = [
                        'olt_id' => $olt->id,
                        'olt_name' => $olt->name,
                        'port' => $port->pon_port,
                        'offline_count' => $port->offline_count,
                        'total_onus' => $port->total,
                        'probability' => min(100, $offlinePercent + 10),
                    ];

                    $existingCut = DB::table('network_event_logs')
                        ->where('event_type', 'fiber_cut')
                        ->where('source', "olt_{$olt->id}_port_{$port->pon_port}")
                        ->where('is_resolved', false)
                        ->exists();

                    if (!$existingCut) {
                        DB::table('network_event_logs')->insert([
                            'event_type' => 'fiber_cut',
                            'source' => "olt_{$olt->id}_port_{$port->pon_port}",
                            'message' => "Fiber cut suspected: {$port->offline_count} of {$port->total} ONUs offline on OLT {$olt->name} Port {$port->pon_port}",
                            'severity' => 'critical',
                            'metadata' => json_encode([
                                'olt_id' => $olt->id,
                                'port' => $port->pon_port,
                                'offline_count' => $port->offline_count,
                                'total' => $port->total,
                            ]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        return $cuts;
    }

    public function getOltDashboard(int $oltId): array
    {
        $olt = OltDevice::find($oltId);
        if (!$olt) {
            return ['error' => 'OLT not found'];
        }

        $onus = DB::table('onus')
            ->where('olt_device_id', $oltId)
            ->get();

        $onlineCount = $onus->where('status', 'online')->count();
        $offlineCount = $onus->where('status', 'offline')->count();

        $portStats = DB::table('onus')
            ->where('olt_device_id', $oltId)
            ->selectRaw('pon_port, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as online', ['online'])
            ->groupBy('pon_port')
            ->get();

        $avgRxPower = $onus->whereNotNull('rx_power')->avg('rx_power');

        $recentEvents = DB::table('onu_events')
            ->whereIn('onu_device_id', $onus->pluck('id'))
            ->orderByDesc('detected_at')
            ->limit(20)
            ->get();

        return [
            'olt' => [
                'id' => $olt->id,
                'name' => $olt->name,
                'ip' => $olt->ip_address,
                'type' => $olt->type ?? $olt->vendor,
            ],
            'summary' => [
                'total_onus' => $onus->count(),
                'online' => $onlineCount,
                'offline' => $offlineCount,
                'online_percent' => $onus->count() > 0 ? round(($onlineCount / $onus->count()) * 100, 1) : 0,
            ],
            'signal_quality' => [
                'avg_rx_power' => $avgRxPower !== null ? round($avgRxPower, 2) : null,
                'quality' => $this->getSignalQuality($avgRxPower),
            ],
            'port_stats' => $portStats,
            'recent_events' => $recentEvents,
        ];
    }

    protected function extractSuffix(string $fullOid, string $baseOid): string
    {
        $baseLen = strlen($baseOid);
        if (str_starts_with($fullOid, $baseOid)) {
            return substr($fullOid, $baseLen);
        }
        if (preg_match('/\.(\d+(?:\.\d+)*)$/', $fullOid, $m)) {
            return '.' . $m[1];
        }
        return '';
    }

    protected function findMatchingValue(array $results, ?string $baseOid, string $suffix): ?string
    {
        if (empty($results) || !$baseOid) return null;

        $fullOid = $baseOid . $suffix;
        if (isset($results[$fullOid])) {
            return $results[$fullOid];
        }

        foreach ($results as $oid => $value) {
            if (str_ends_with($oid, $suffix)) {
                return $value;
            }
        }

        return null;
    }
}
