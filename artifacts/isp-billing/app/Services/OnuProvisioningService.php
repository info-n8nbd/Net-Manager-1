<?php

namespace App\Services;

use App\Models\OltDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnuProvisioningService
{
    public function discoverUnregistered(int $oltId): array
    {
        $olt = OltDevice::find($oltId);
        if (!$olt || !$olt->is_active) {
            return ['success' => false, 'message' => 'OLT device not available', 'data' => []];
        }

        try {
            $api = OltService::fromDevice($olt);
            if (!$api->connect()) {
                return ['success' => false, 'message' => 'Failed to connect to OLT', 'data' => []];
            }

            $onus = $api->discoverUnregisteredOnus();
            $api->disconnect();

            $existing = DB::table('onus')
                ->where('olt_device_id', $oltId)
                ->pluck('serial_number')
                ->toArray();

            $unregistered = array_filter($onus, function ($onu) use ($existing) {
                return !in_array($onu['serial'], $existing);
            });

            return [
                'success' => true,
                'message' => count($unregistered) . ' unregistered ONU(s) found',
                'data' => array_values($unregistered),
            ];
        } catch (\Exception $e) {
            Log::error("ONU discovery failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public function provision(int $oltId, string $serial, string $port, array $options = []): array
    {
        $olt = OltDevice::find($oltId);
        if (!$olt || !$olt->is_active) {
            return ['success' => false, 'message' => 'OLT device not available'];
        }

        $lineProfile = $options['line_profile'] ?? 'default';
        $serviceProfile = $options['service_profile'] ?? 'default';
        $description = $options['description'] ?? null;
        $vlan = $options['vlan'] ?? null;
        $userId = $options['user_id'] ?? null;

        try {
            $api = OltService::fromDevice($olt);
            if (!$api->connect()) {
                return ['success' => false, 'message' => 'Failed to connect to OLT'];
            }

            $result = $api->provisionOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
            $api->disconnect();

            $existingOnu = DB::table('onus')->where('serial_number', $serial)->first();
            $onuData = [
                'olt_device_id' => $oltId,
                'serial_number' => $serial,
                'pon_port' => $port,
                'onu_number' => $result['onu_id'] ?? null,
                'description' => $description,
                'status' => 'online',
                'vlan' => $vlan,
                'service_profile' => $serviceProfile,
                'line_profile' => $lineProfile,
                'is_provisioned' => true,
                'provisioned_at' => now(),
                'provisioned_by' => $userId,
                'updated_at' => now(),
            ];
            if ($existingOnu) {
                DB::table('onus')->where('id', $existingOnu->id)->update($onuData);
                $onuDbId = $existingOnu->id;
            } else {
                $onuData['created_at'] = now();
                $onuDbId = DB::table('onus')->insertGetId($onuData);
            }

            DB::table('onu_provision_logs')->insert([
                'onu_id' => $onuDbId,
                'olt_device_id' => $oltId,
                'serial_number' => $serial,
                'action' => 'provision',
                'pon_port' => $port,
                'onu_number' => $result['onu_id'] ?? null,
                'service_profile' => $serviceProfile,
                'line_profile' => $lineProfile,
                'vlan' => $vlan,
                'result' => $result['success'] ? 'success' : 'failed',
                'result_message' => $result['message'],
                'command_output' => $result['output'] ?? null,
                'performed_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => ['onu_id' => $onuDbId, 'onu_number' => $result['onu_id'] ?? null],
            ];
        } catch (\Exception $e) {
            Log::error("ONU provision failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function decommission(int $onuId, int $userId, string $reason = ''): array
    {
        $onu = DB::table('onus')->find($onuId);
        if (!$onu) {
            return ['success' => false, 'message' => 'ONU not found'];
        }

        $olt = OltDevice::find($onu->olt_device_id);
        if (!$olt || !$olt->is_active) {
            return ['success' => false, 'message' => 'OLT device not available'];
        }

        try {
            $api = OltService::fromDevice($olt);
            if (!$api->connect()) {
                return ['success' => false, 'message' => 'Failed to connect to OLT'];
            }

            $result = $api->decommissionOnu($onu->pon_port, $onu->onu_number);
            $api->disconnect();

            DB::table('onus')->where('id', $onuId)->update([
                'status' => 'decommissioned',
                'is_provisioned' => false,
                'client_id' => null,
                'updated_at' => now(),
            ]);

            // Clear all ONU-related fields from the client record
            if ($onu->client_id) {
                DB::table('clients')->where('id', $onu->client_id)->update([
                    'olt_device_id'      => null,
                    'olt_id'             => null,
                    'olt_port'           => null,
                    'onu_id'             => null,
                    'onu_serial'         => null,
                    'onu_mac'            => null,
                    'onu_status'         => 'unknown',
                    'onu_rx_power'       => null,
                    'onu_tx_power'       => null,
                    'onu_temperature'    => null,
                    'onu_signal'         => null,
                    'onu_signal_quality' => null,
                    'onu_down_reason'    => null,
                    'onu_uptime_seconds' => null,
                    'updated_at'         => now(),
                ]);
            }

            DB::table('onu_provision_logs')->insert([
                'onu_id' => $onuId,
                'olt_device_id' => $onu->olt_device_id,
                'serial_number' => $onu->serial_number,
                'action' => 'decommission',
                'pon_port' => $onu->pon_port,
                'onu_number' => $onu->onu_number,
                'result' => $result['success'] ? 'success' : 'failed',
                'result_message' => $reason . ' | ' . $result['message'],
                'command_output' => $result['output'] ?? null,
                'performed_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => $result['success'],
                'message' => $result['message'],
            ];
        } catch (\Exception $e) {
            Log::error("ONU decommission failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function assignToClient(int $onuId, int $clientId): array
    {
        $onu = DB::table('onus')->find($onuId);
        if (!$onu) {
            return ['success' => false, 'message' => 'ONU not found'];
        }

        $client = DB::table('clients')->find($clientId);
        if (!$client) {
            return ['success' => false, 'message' => 'Client not found'];
        }

        DB::table('onus')->where('id', $onuId)->update([
            'client_id' => $clientId,
            'updated_at' => now(),
        ]);

        // Also update the client record with ONU info
        $clientUpdate = [];
        if ($onu->serial_number)  $clientUpdate['onu_serial']   = $onu->serial_number;
        if ($onu->mac_address)    $clientUpdate['onu_mac']       = $onu->mac_address;
        if ($onu->pon_port)       $clientUpdate['olt_port']      = $onu->pon_port;
        if ($onu->olt_device_id)  $clientUpdate['olt_device_id'] = $onu->olt_device_id;
        $clientUpdate['onu_id'] = $onuId;
        DB::table('clients')->where('id', $clientId)->update($clientUpdate);

        return [
            'success' => true,
            'message' => 'ONU ' . ($onu->serial_number ?? '') . ' assigned to client ' . ($client->name ?? $client->client_id ?? $clientId),
            'onu' => [
                'id'            => $onu->id,
                'serial'        => $onu->serial_number,
                'mac'           => $onu->mac_address,
                'port'          => $onu->pon_port,
                'olt_device_id' => $onu->olt_device_id,
            ],
        ];
    }

    public function unassignFromClient(int $onuId): array
    {
        $onu = DB::table('onus')->find($onuId);
        if (!$onu) {
            return ['success' => false, 'message' => 'ONU not found'];
        }

        $oldClientId = $onu->client_id;

        DB::table('onus')->where('id', $onuId)->update([
            'client_id' => null,
            'updated_at' => now(),
        ]);

        // Also clear client record (including stale optical data)
        if ($oldClientId) {
            DB::table('clients')->where('id', $oldClientId)->update([
                'olt_device_id'      => null,
                'olt_id'             => null,
                'olt_port'           => null,
                'onu_id'             => null,
                'onu_serial'         => null,
                'onu_mac'            => null,
                'onu_status'         => 'unknown',
                'onu_rx_power'       => null,
                'onu_tx_power'       => null,
                'onu_temperature'    => null,
                'onu_signal'         => null,
                'onu_signal_quality' => null,
                'onu_down_reason'    => null,
                'onu_uptime_seconds' => null,
                'updated_at'         => now(),
            ]);
        }

        return [
            'success' => true,
            'message' => 'ONU unassigned from client',
        ];
    }

    public function getOnuList(int $oltId, array $filters = []): object
    {
        $query = DB::table('onus')
            ->leftJoin('clients', 'onus.client_id', '=', 'clients.id')
            ->where('onus.olt_device_id', $oltId)
            ->select(
                'onus.*',
                'clients.name as client_name',
                'clients.client_id as client_code',
                'clients.username as client_username'
            );

        if (!empty($filters['status'])) {
            $query->where('onus.status', $filters['status']);
        }
        if (!empty($filters['port'])) {
            $query->where('onus.pon_port', $filters['port']);
        }
        if (isset($filters['assigned'])) {
            if ($filters['assigned'] === 'yes') {
                $query->whereNotNull('onus.client_id');
            } elseif ($filters['assigned'] === 'no') {
                $query->whereNull('onus.client_id');
            }
        }
        if (!empty($filters['signal_quality'])) {
            $sq = $filters['signal_quality'];
            if ($sq === 'excellent') {
                $query->where('onus.rx_power', '>=', -15);
            } elseif ($sq === 'good') {
                $query->where('onus.rx_power', '>=', -20)->where('onus.rx_power', '<', -15);
            } elseif ($sq === 'fair') {
                $query->where('onus.rx_power', '>=', -25)->where('onus.rx_power', '<', -20);
            } elseif ($sq === 'poor') {
                $query->where('onus.rx_power', '<', -25);
            }
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('onus.serial_number', 'ilike', "%{$search}%")
                  ->orWhere('onus.mac_address', 'ilike', "%{$search}%")
                  ->orWhere('onus.description', 'ilike', "%{$search}%")
                  ->orWhere('clients.name', 'ilike', "%{$search}%")
                  ->orWhere('clients.client_id', 'ilike', "%{$search}%")
                  ->orWhere('clients.username', 'ilike', "%{$search}%");
            });
        }

                return $query->orderByRaw("CAST(NULLIF(REGEXP_REPLACE(onus.pon_port, '[^0-9]', '', 'g'), '') AS INTEGER) ASC")->orderBy('onus.onu_number')->paginate(25);
    }

    public function getProvisionLogs(int $oltId, int $limit = 50): array
    {
        return DB::table('onu_provision_logs')
            ->leftJoin('users', 'onu_provision_logs.performed_by', '=', 'users.id')
            ->where('onu_provision_logs.olt_device_id', $oltId)
            ->select(
                'onu_provision_logs.*',
                'users.name as performed_by_name'
            )
            ->orderByDesc('onu_provision_logs.created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
