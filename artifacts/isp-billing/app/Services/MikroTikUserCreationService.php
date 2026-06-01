<?php

namespace App\Services;

use App\Models\NewConnectionApplication;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MikroTikUserCreationService
{
    protected int $maxRetries = 3;
    protected int $retryDelaySeconds = 2;

    public function createUserFromApplication(NewConnectionApplication $application, Client $client): array
    {
        $device = $this->selectMikroTikDevice($application);
        
        if (!$device) {
            Log::warning("No MikroTik device available for application {$application->id}");
            return [
                'success' => false,
                'error' => 'No MikroTik device available for this zone',
                'username' => null,
                'device_id' => null,
            ];
        }

        $package = $application->package;
        if (!$package) {
            return [
                'success' => false,
                'error' => 'No package assigned to application',
                'username' => null,
                'device_id' => null,
            ];
        }

        $username = $this->generateUsername($client);
        $password = !empty($client->pppoe_password) ? $client->pppoe_password : $this->generatePassword();

        $result = $this->createPPPoEUser($device, $username, $password, $package, $client);

        if ($result['success']) {
            $client->update([
                'pppoe_username' => $username,
                'pppoe_password' => $password,
                'mikrotik_device_id' => $device->id,
            ]);

            Log::info("MikroTik user created for client {$client->client_id}", [
                'username' => $username,
                'device' => $device->name,
                'package' => $package->name,
            ]);
        }

        return array_merge($result, [
            'username' => $username,
            'password' => $password,
            'device_id' => $device->id,
            'device_name' => $device->name,
        ]);
    }

    protected function selectMikroTikDevice(NewConnectionApplication $application): ?MikrotikDevice
    {
        if ($application->mikrotik_device_id) {
            return MikrotikDevice::find($application->mikrotik_device_id);
        }

        return MikrotikDevice::where('zone_id', $application->zone_id)
            ->where('is_active', true)
            ->first()
            ?? MikrotikDevice::where('is_active', true)
                ->first();
    }

    protected function generateUsername(Client $client): string
    {
        if (!empty($client->username)) {
            return $client->username;
        }

        if (!empty($client->pppoe_username)) {
            return $client->pppoe_username;
        }

        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $client->name));
        $base = substr($base, 0, 12);

        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;
        while (Client::where('username', $username)->where('id', '!=', $client->id)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    protected function generatePassword(): string
    {
        return Str::random(12);
    }

    protected function connectToDevice(MikrotikDevice $device): ?SimpleMikroTikService
    {
        $mikrotik = new SimpleMikroTikService();
        $connected = $mikrotik->connect(
            $device->host,
            $device->username,
            $device->password,
            (int)($device->getRawOriginal('port') ?? $device->port ?? 8728)
        );

        if (!$connected) {
            Log::error("Cannot connect to MikroTik device: {$device->name} ({$device->host})");
            return null;
        }

        return $mikrotik;
    }

    protected function createPPPoEUser(MikrotikDevice $device, string $username, string $password, Package $package, Client $client): array
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;
            
            try {
                $mikrotik = $this->connectToDevice($device);
                if (!$mikrotik) {
                    $lastError = "Cannot connect to MikroTik device: {$device->name}";
                    if ($attempt < $this->maxRetries) {
                        sleep($this->retryDelaySeconds);
                    }
                    continue;
                }

                $profileName = $client->mikrotik_profile_name ?? $package->mikrotik_profile ?? $package->name;
                $comment = "Client: {$client->client_id} - {$client->name}";
                $connectionType = strtolower($client->connection_type ?? 'pppoe');

                switch ($connectionType) {
                    case 'hotspot':
                        $result = $mikrotik->createHotspotUser($username, $password, $profileName, $comment, false);
                        break;
                    case 'static':
                    case 'static_ip':
                        $maxLimit = $this->getSpeedLimit($package);
                        $target = $client->ip_address ?? '0.0.0.0/32';
                        $result = $mikrotik->createSimpleQueue($username, $target, $maxLimit, $comment);
                        if ($result['success'] && $client->ip_address) {
                            $mikrotik->addToAddressList($client->ip_address, 'allowed_clients', $comment);
                        }
                        break;
                    case 'dhcp':
                        if ($client->mac_address) {
                            $mikrotik->createDhcpLease($client->ip_address ?? '', $client->mac_address, $comment);
                        }
                        $maxLimit = $this->getSpeedLimit($package);
                        $target = $client->ip_address ?? '0.0.0.0/32';
                        $result = $mikrotik->createSimpleQueue($username, $target, $maxLimit, $comment);
                        break;
                    case 'pppoe':
                    default:
                        $result = $mikrotik->createPPPoESecret($username, $password, $profileName, $comment, false);
                        break;
                }

                if ($result['success']) {
                    $this->logMikroTikAction($client, $device, 'create_user', $connectionType, 'success', null,
                        ['username' => $username, 'profile' => $profileName, 'connection_type' => $connectionType]);
                    return $result;
                }
                
                $lastError = $result['message'] ?? 'Unknown error';
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("MikroTik creation attempt {$attempt} failed: {$lastError}");
            }

            if ($attempt < $this->maxRetries) {
                sleep($this->retryDelaySeconds);
            }
        }

        $this->logMikroTikAction($client, $device, 'create_user', $connectionType ?? 'pppoe', 'failed', $lastError,
            ['username' => $username, 'attempts' => $this->maxRetries]);

        Log::error("MikroTik user creation failed after {$this->maxRetries} attempts", [
            'device' => $device->name,
            'username' => $username,
            'error' => $lastError,
        ]);

        return [
            'success' => false,
            'error' => "Failed after {$this->maxRetries} attempts: {$lastError}",
            'attempts' => $attempt,
        ];
    }

    public function enableUser(MikrotikDevice $device, string $username, string $connectionType = 'pppoe'): array
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return ['success' => false, 'message' => "Cannot connect to {$device->name}"];
        }

        switch (strtolower($connectionType)) {
            case 'hotspot':
                return $mikrotik->enableHotspotUser($username);
            case 'static':
            case 'static_ip':
            case 'dhcp':
                return $mikrotik->enableSimpleQueue($username);
            case 'pppoe':
            default:
                return $mikrotik->enablePPPoESecret($username);
        }
    }

    public function disableUser(MikrotikDevice $device, string $username, string $connectionType = 'pppoe'): array
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return ['success' => false, 'message' => "Cannot connect to {$device->name}"];
        }

        switch (strtolower($connectionType)) {
            case 'hotspot':
                return $mikrotik->disableHotspotUser($username);
            case 'static':
            case 'static_ip':
            case 'dhcp':
                return $mikrotik->disableSimpleQueue($username);
            case 'pppoe':
            default:
                return $mikrotik->disablePPPoESecret($username);
        }
    }

    public function deleteUser(MikrotikDevice $device, string $username, string $connectionType = 'pppoe'): array
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return ['success' => false, 'message' => "Cannot connect to {$device->name}"];
        }

        switch (strtolower($connectionType)) {
            case 'hotspot':
                return $mikrotik->deleteHotspotUser($username);
            case 'static':
            case 'static_ip':
                $mikrotik->deleteSimpleQueue($username);
                return ['success' => true, 'message' => 'Static client removed'];
            case 'dhcp':
                $mikrotik->deleteSimpleQueue($username);
                return ['success' => true, 'message' => 'DHCP client removed'];
            case 'pppoe':
            default:
                return $mikrotik->deletePPPoESecret($username);
        }
    }

    public function updateUserProfile(MikrotikDevice $device, string $username, string $newProfile, string $connectionType = 'pppoe'): array
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return ['success' => false, 'message' => "Cannot connect to {$device->name}"];
        }

        switch (strtolower($connectionType)) {
            case 'hotspot':
                $mikrotik->deleteHotspotUser($username);
                return $mikrotik->createHotspotUser($username, '', $newProfile, '', false);
            case 'static':
            case 'static_ip':
            case 'dhcp':
                $package = \App\Models\Package::where('name', $newProfile)->first();
                $maxLimit = $this->getSpeedLimit($package);
                return $mikrotik->updateSimpleQueueLimit($username, $maxLimit);
            case 'pppoe':
            default:
                return $mikrotik->updatePPPoEProfile($username, $newProfile);
        }
    }

    public function createQueue(MikrotikDevice $device, Client $client, Package $package): array
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return ['success' => false, 'message' => "Cannot connect to {$device->name}"];
        }

        $queueName = "queue_{$client->client_id}";
        $target = $client->ip_address ?? '0.0.0.0/32';
        $maxLimit = $this->getSpeedLimit($package);
        $comment = "Client: {$client->client_id} - {$client->name}";

        return $mikrotik->createSimpleQueue($queueName, $target, $maxLimit, $comment);
    }

    protected function getSpeedLimit(?Package $package): string
    {
        if (!$package) {
            return '10M/10M';
        }
        $upload = $package->upload_speed ?? $package->bandwidth ?? 10;
        $download = $package->download_speed ?? $package->bandwidth ?? 10;
        $unit = $package->speed_unit ?? 'M';
        return "{$upload}{$unit}/{$download}{$unit}";
    }

    protected function logMikroTikAction(?Client $client, ?MikrotikDevice $device, string $action, string $actionType, string $status, ?string $errorMessage = null, array $requestData = [], array $responseData = []): void
    {
        try {
            DB::table('mikrotik_action_logs')->insert([
                'client_id' => $client?->id,
                'device_id' => $device?->id,
                'action' => $action,
                'action_type' => $actionType,
                'username' => $client?->username,
                'router_ip' => $device?->ip_address ?? $device?->host,
                'trigger_source' => 'auto_provision',
                'triggered_by' => auth()->id(),
                'status' => $status,
                'error_message' => $errorMessage,
                'request_data' => json_encode($requestData),
                'response_data' => json_encode($responseData),
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log MikroTik action: " . $e->getMessage());
        }
    }
}
