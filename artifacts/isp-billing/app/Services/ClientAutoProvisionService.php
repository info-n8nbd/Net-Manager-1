<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientAutoProvisionService
{
    public function provisionNewClient(Client $client): array
    {
        $connectionType = $client->connection_type ?? 'pppoe';
        $username = $client->pppoe_username ?? $client->username;

        if (empty($username) && !in_array($connectionType, ['static', 'dhcp'])) {
            return ['success' => false, 'message' => 'Client has no username configured'];
        }

        $device = $this->findMikrotikDevice($client);
        if (!$device) {
            return ['success' => false, 'message' => 'No active MikroTik device found'];
        }

        $mikrotik = new SimpleMikroTikService();
        $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

        if (!$connected) {
            $this->logOperation($client, 'provision', 'failed', "Cannot connect to MikroTik {$device->name}");
            return ['success' => false, 'message' => "Cannot connect to MikroTik device: {$device->name}"];
        }

        $client->loadMissing('package');
        $password = $client->pppoe_password ?? '123456';

        $result = $mikrotik->createClientByType($client, $password);

        if ($result['success']) {
            if (!$client->mikrotik_device_id) {
                $client->update(['mikrotik_device_id' => $device->id]);
            }

            $this->logOperation($client, 'provision', 'success', "Provisioned as {$connectionType} on {$device->name}");

            if ($client->olt_device_id && $client->onu_serial) {
                try {
                    $deviceControlService = app(DeviceControlService::class);
                    $deviceControlService->syncClientFromOlt($client);
                } catch (\Exception $e) {
                    Log::warning("ONU sync during provision failed for {$client->name}: " . $e->getMessage());
                }
            }

            return ['success' => true, 'message' => "Client provisioned successfully as {$connectionType}"];
        }

        $this->logOperation($client, 'provision', 'failed', $result['message'] ?? 'Unknown error');
        return ['success' => false, 'message' => $result['message'] ?? 'Provisioning failed'];
    }

    public function deprovisionClient(Client $client): array
    {
        $device = $this->findMikrotikDevice($client);
        if (!$device) {
            return ['success' => false, 'message' => 'No MikroTik device found for deprovisioning'];
        }

        $mikrotik = new SimpleMikroTikService();
        $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

        if (!$connected) {
            $this->logOperation($client, 'deprovision', 'failed', "Cannot connect to MikroTik {$device->name}");
            return ['success' => false, 'message' => "Cannot connect to MikroTik device: {$device->name}"];
        }

        $connectionType = $client->connection_type ?? 'pppoe';
        $username = $client->pppoe_username ?? $client->username;
        $queueName = $username ?: "client-{$client->id}";

        $result = ['success' => false, 'message' => 'Unknown connection type'];

        switch (strtolower($connectionType)) {
            case 'pppoe':
                $result = $mikrotik->deletePPPoESecret($username);
                break;
            case 'hotspot':
                $result = $mikrotik->deleteHotspotUser($username);
                break;
            case 'static':
                $mikrotik->deleteSimpleQueue($queueName);
                if ($client->ip_address) {
                    $mikrotik->removeFromAddressList('allowed_clients', $client->ip_address);
                }
                $result = ['success' => true, 'message' => 'Static client removed'];
                break;
            case 'dhcp':
                if ($client->mac_address) {
                    $mikrotik->deleteDhcpLease($client->mac_address);
                }
                $mikrotik->deleteSimpleQueue($queueName);
                $result = ['success' => true, 'message' => 'DHCP client removed'];
                break;
        }

        $this->logOperation($client, 'deprovision', $result['success'] ? 'success' : 'failed', $result['message'] ?? '');
        return $result;
    }

    protected function findMikrotikDevice(Client $client): ?MikrotikDevice
    {
        if ($client->mikrotik_device_id) {
            $device = MikrotikDevice::where('id', $client->mikrotik_device_id)->where('is_active', true)->first();
            if ($device) {
                return $device;
            }
        }

        if ($client->zone_id) {
            $device = MikrotikDevice::where('zone_id', $client->zone_id)->where('is_active', true)->first();
            if ($device) {
                return $device;
            }
        }

        return MikrotikDevice::where('is_active', true)->first();
    }

    protected function logOperation(Client $client, string $operation, string $result, string $message = ''): void
    {
        try {
            $deviceId = $client->mikrotik_device_id;
            if (!$deviceId) {
                $device = $this->findMikrotikDevice($client);
                $deviceId = $device?->id;
            }
            if (!$deviceId) {
                Log::info("Skipping device operation log (no device_id): {$operation} - {$result} - {$message}");
                return;
            }
            DB::table('device_operation_logs')->insert([
                'device_type' => 'mikrotik',
                'device_id' => $deviceId,
                'client_id' => $client->id,
                'operation' => $operation,
                'connection_type' => $client->connection_type ?? 'pppoe',
                'parameters' => json_encode([
                    'username' => $client->username,
                    'package' => $client->package?->name,
                    'ip_address' => $client->ip_address,
                ]),
                'result' => $result,
                'result_message' => $message,
                'performed_by' => auth()->id(),
                'ip_address' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log device operation: " . $e->getMessage());
        }
    }
}
