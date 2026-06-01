<?php
/**
 * DeviceControlService - Manages MikroTik/OLT device operations
 * 
 * SUPPORTS ALL CONNECTION TYPES:
 * - PPPoE (most common in Bangladesh residential ISP)
 * - Hotspot (cybercafe, hotels, public WiFi)
 * - Static IP (business/enterprise clients)
 * - DHCP (dynamic IP assignment)
 * 
 * CRITICAL: Package name = Profile/Queue name
 * - For PPPoE/Hotspot: Package name matches MikroTik PPP profile name
 * - For Static/DHCP: Package name matches Simple Queue name
 */

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\MikrotikActionLog;
use App\Models\OltDevice;
use App\Services\SimpleMikroTikService;
use App\Services\SmsService;
use App\Services\OltService;
use App\Events\MikroTikActionExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DeviceControlService
{
    public function syncClientFromMikrotik(Client $client): array
    {
        $result = [
            'success' => false,
            'not_configured' => false,
            'message' => 'MikroTik sync not configured',
            'data' => []
        ];

        $device = null;

        // 1. Try assigned device
        if ($client->mikrotik_device_id) {
            $d = MikrotikDevice::find($client->mikrotik_device_id);
            if ($d && $d->is_active) {
                $device = $d;
            }
        }

        // 2. Zone-based fallback
        if (!$device && $client->zone_id) {
            $device = MikrotikDevice::where('zone_id', $client->zone_id)->where('is_active', true)->first();
        }

        // 3. Any active device fallback
        if (!$device) {
            $device = MikrotikDevice::where('is_active', true)->first();
        }

        if (!$device) {
            $result['not_configured'] = true;
            $result['message'] = 'No MikroTik device available for this client';
            return $result;
        }

        try {
            $sessionData = $this->getMikrotikPppoeSession($device, $client->username);
            
            if ($sessionData) {
                $client->update([
                    'connection_status' => 'online',
                    'last_online' => now(),
                    'session_uptime' => $sessionData['uptime'] ?? null,
                    'session_ip' => $sessionData['address'] ?? null,
                    'session_mac' => $sessionData['caller-id'] ?? null,
                    'current_rx_speed' => $sessionData['rx-rate'] ?? null,
                    'current_tx_speed' => $sessionData['tx-rate'] ?? null,
                ]);
                $result['success'] = true;
                $result['message'] = 'Client is online';
                $result['data'] = $sessionData;
            } else {
                $client->update([
                    'connection_status' => 'offline',
                    'last_logout' => now(),
                    'session_ip' => null,
                    'session_mac' => null,
                    'session_uptime' => null,
                ]);
                $result['success'] = true;
                $result['message'] = 'Client is offline';
            }
        } catch (\Exception $e) {
            Log::error("MikroTik sync error for client {$client->id}: " . $e->getMessage());
            $result['message'] = 'Error connecting to MikroTik: ' . $e->getMessage();
        }

        return $result;
    }

    public function syncClientFromOlt(Client $client): array
    {
        $result = [
            'success' => false,
            'not_configured' => false,
            'message' => 'OLT sync not configured',
            'data' => []
        ];

        if (!$client->olt_device_id || !$client->onu_serial) {
            $result['not_configured'] = true;
            return $result;
        }

        $device = OltDevice::find($client->olt_device_id);
        if (!$device) {
            $result['message'] = 'OLT device not found';
            return $result;
        }

        try {
            $onuData = $this->getOnuStatus($device, $client->onu_serial, $client->olt_port);
            
            if ($onuData) {
                $updateData = [
                    'onu_status' => $onuData['status'] ?? 'unknown',
                    'onu_rx_power' => $onuData['rx_power'] ?? null,
                    'onu_tx_power' => $onuData['tx_power'] ?? null,
                    'onu_signal_quality' => $this->calculateSignalQuality($onuData['rx_power'] ?? null),
                    'onu_temperature' => $onuData['temperature'] ?? null,
                    'onu_uptime_seconds' => $onuData['uptime'] ?? null,
                    'onu_last_check' => now(),
                ];

                if ($onuData['status'] === 'offline' && ($client->onu_status ?? 'unknown') !== 'offline') {
                    $updateData['onu_last_down'] = now();
                    $updateData['onu_down_reason'] = $onuData['down_reason'] ?? 'Connection lost';
                }

                if ($onuData['status'] === 'online') {
                    $updateData['onu_down_reason'] = null;
                }

                $client->update($updateData);
                $result['success'] = true;
                $result['message'] = "ONU is {$onuData['status']}";
                $result['data'] = $onuData;
            }
        } catch (\Exception $e) {
            Log::error("OLT sync error for client {$client->id}: " . $e->getMessage());
            $result['message'] = 'Error connecting to OLT: ' . $e->getMessage();
        }

        return $result;
    }

    public function disconnectClient(Client $client, string $reason = 'Manual disconnect'): array
    {
        $result = [
            'success' => false,
            'message' => 'Disconnect failed',
        ];

        if (!$client->mikrotik_device_id) {
            $result['message'] = 'No MikroTik device assigned';
            return $result;
        }

        $device = MikrotikDevice::find($client->mikrotik_device_id);
        if (!$device) {
            $result['message'] = 'MikroTik device not found';
            return $result;
        }

        try {
            $disconnected = $this->disconnectPppoeSession($device, $client->username);
            
            if ($disconnected) {
                $client->update([
                    'connection_status' => 'offline',
                    'last_logout' => now(),
                ]);
                $result['success'] = true;
                $result['message'] = "Client disconnected: {$reason}";
                
                Log::info("Client {$client->id} disconnected: {$reason}");
            }
        } catch (\Exception $e) {
            Log::error("Disconnect error for client {$client->id}: " . $e->getMessage());
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Enable client on MikroTik (based on connection type)
     * Called when: payment received, manual activation, service restoration
     * 
     * Supports: PPPoE, Hotspot, Static IP, DHCP
     * 
     * @param Client $client
     * @param bool $sendSms
     * @return array
     */
    public function enableClient(Client $client, bool $sendSms = false): array
    {
        $mikrotik = new SimpleMikroTikService();
        $connectionType = $client->connection_type ?? 'pppoe';
        $username = $client->pppoe_username ?? $client->username;
        
        if (empty($username)) {
            Log::error("Enable failed: Client {$client->id} has no username");
            return [
                'success' => false,
                'message' => 'Client has no username configured'
            ];
        }

        // CRITICAL: Use client's assigned MikroTik device, not default
        $connected = false;
        $device = null;
        
        if ($client->mikrotik_device_id) {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if ($device && $device->is_active) {
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            }
        }
        
        // Fallback to zone-based router selection
        if (!$connected && $client->zone_id) {
            $device = MikrotikDevice::where('zone_id', $client->zone_id)->where('is_active', true)->first();
            if ($device) {
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            }
        }
        
        // Last resort: use default router
        if (!$connected) {
            $connected = $mikrotik->connectToDefault();
        }
        
        if (!$connected) {
            Log::error("Enable failed: Cannot connect to MikroTik router for client {$client->id}");
            return [
                'success' => false,
                'message' => 'Cannot connect to MikroTik router'
            ];
        }

        // Route to appropriate method based on connection type
        switch (strtolower($connectionType)) {
            case 'hotspot':
                $result = $mikrotik->enableHotspotUser($username);
                break;
            case 'static':
            case 'static_ip':
                // For static IP, enable by removing from disabled address list
                $result = $mikrotik->removeFromAddressList($client->ip_address, 'disabled_clients');
                break;
            case 'dhcp':
                // For DHCP, enable simple queue
                $result = $mikrotik->enableSimpleQueue($username);
                break;
            case 'pppoe':
            default:
                $client->loadMissing('package');
                // Priority: previous_mikrotik_profile (saved during disable) → package profile
                $originalProfile = null;
                if (!empty($client->previous_mikrotik_profile) && strtolower($client->previous_mikrotik_profile) !== 'inactive') {
                    $originalProfile = $client->previous_mikrotik_profile;
                }
                if (empty($originalProfile)) {
                    $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
                }
                if ($originalProfile) {
                    $currentProfile = $mikrotik->getPPPoEUserProfile($username);
                    if ($currentProfile === 'Inactive' || ($currentProfile && strtolower($currentProfile) !== strtolower($originalProfile))) {
                        $mikrotik->changePPPoEProfile($username, $originalProfile);
                        Log::info("EnableClient: Restored MikroTik profile for {$username} from '{$currentProfile}' to '{$originalProfile}'");
                    }
                }
                $result = $mikrotik->enablePPPoESecret($username);
                break;
        }
        
        if ($result['success']) {
            $oldStatus = $client->status;
            $dbUpdate = [
                'status' => 'active',
                'last_active_date' => now(),
                'previous_mikrotik_profile' => null,
            ];
            // Sync the correct profile name to DB so the UI toggle reflects real state
            if (strtolower($connectionType) === 'pppoe' && !empty($originalProfile)) {
                $dbUpdate['mikrotik_profile_name'] = $originalProfile;
            }
            $client->update($dbUpdate);
            
            Log::info("Client enabled on MikroTik ({$connectionType}): {$client->client_id} ({$username})");
            $this->logRouterAction($client, $device, 'enable', $connectionType, 'success');
            event(new MikroTikActionExecuted('enable', $client, $device, true, "Enabled {$connectionType} user {$username}", 'service'));
            
            if ($oldStatus !== 'active') {
                ClientActivityService::logStatusChange($client, $oldStatus, 'active', 'Enabled on MikroTik', 'manual');
                
                try {
                    $telegram = new TelegramService();
                    $telegram->sendClientConnected($client->client_id ?? $client->id, null, 'Online', $client->name ?? '', $client->username ?? '');
                } catch (\Throwable $e) {
                    Log::warning('Telegram connect notification failed: ' . $e->getMessage());
                }
            }
            
            if ($sendSms) {
                try {
                    app(SmsService::class)->sendReconnectNotice($client);
                } catch (\Exception $e) {
                    Log::warning("SMS failed for client {$client->id}: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'message' => "Client enabled successfully ({$connectionType})"
            ];
        }

        Log::error("Enable failed for {$username}: " . $result['message']);
        $this->logRouterAction($client, $device, 'enable', $connectionType, 'failed', $result['message'] ?? 'Enable failed');
        event(new MikroTikActionExecuted('enable', $client, $device, false, $result['message'] ?? 'Enable failed', 'service'));
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Enable failed'
        ];
    }

    /**
     * Disable client on MikroTik (based on connection type)
     * Called when: overdue payment, manual suspension, service termination
     * 
     * Supports: PPPoE, Hotspot, Static IP, DHCP
     * 
     * @param Client $client
     * @param string $reason
     * @param bool $sendSms
     * @return array
     */
    public function disableClient(Client $client, string $reason = 'Manual disable', bool $sendSms = false): array
    {
        $mikrotik = new SimpleMikroTikService();
        $connectionType = $client->connection_type ?? 'pppoe';
        $username = $client->pppoe_username ?? $client->username;
        
        if (empty($username)) {
            Log::error("Disable failed: Client {$client->id} has no username");
            return [
                'success' => false,
                'message' => 'Client has no username configured'
            ];
        }

        // CRITICAL: Use client's assigned MikroTik device, not default
        $connected = false;
        $device = null;
        
        if ($client->mikrotik_device_id) {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if ($device && $device->is_active) {
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            }
        }
        
        // Fallback to zone-based router selection
        if (!$connected && $client->zone_id) {
            $device = MikrotikDevice::where('zone_id', $client->zone_id)->where('is_active', true)->first();
            if ($device) {
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            }
        }
        
        // Last resort: use default router
        if (!$connected) {
            $connected = $mikrotik->connectToDefault();
        }
        
        if (!$connected) {
            Log::error("Disable failed: Cannot connect to MikroTik router for client {$client->id}");
            return [
                'success' => false,
                'message' => 'Cannot connect to MikroTik router'
            ];
        }

        // Route to appropriate method based on connection type
        switch (strtolower($connectionType)) {
            case 'hotspot':
                $result = $mikrotik->disableHotspotUser($username);
                break;
            case 'static':
            case 'static_ip':
                // For static IP, disable by adding to disabled address list
                $result = $mikrotik->addToAddressList($client->ip_address, 'disabled_clients', $reason);
                break;
            case 'dhcp':
                // For DHCP, disable simple queue
                $result = $mikrotik->disableSimpleQueue($username);
                break;
            case 'pppoe':
            default:
                $result = $mikrotik->disablePPPoESecret($username);
                break;
        }
        
        if ($result['success']) {
            $oldStatus = $client->status;
            $client->update([
                'status' => 'inactive',
                'connection_status' => 'offline',
                'last_inactive_date' => now(),
                'auto_inactive_note' => null,
            ]);
            
            Log::info("Client disabled on MikroTik ({$connectionType}): {$client->client_id} ({$username}) - Reason: {$reason}");
            $this->logRouterAction($client, $device, 'disable', $connectionType, 'success', $reason);
            event(new MikroTikActionExecuted('disable', $client, $device, true, "Disabled {$connectionType} user {$username}: {$reason}", 'service'));
            
            if ($oldStatus !== 'inactive') {
                ClientActivityService::logStatusChange($client, $oldStatus, 'inactive', $reason, 'manual');
                
                try {
                    $telegram = new TelegramService();
                    $telegram->sendClientDisconnected($client->client_id ?? $client->id, $reason, null, 'Offline', 'Account disabled', $client->name ?? '', $client->username ?? '');
                } catch (\Throwable $e) {
                    Log::warning('Telegram disconnect notification failed: ' . $e->getMessage());
                }
            }
            
            if ($sendSms) {
                try {
                    app(SmsService::class)->sendDisconnectNotice($client);
                } catch (\Exception $e) {
                    Log::warning("SMS failed for client {$client->id}: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'message' => "Client disabled ({$connectionType}): {$reason}"
            ];
        }

        Log::error("Disable failed for {$username}: " . $result['message']);
        event(new MikroTikActionExecuted('disable', $client, $device, false, $result['message'] ?? 'Disable failed', 'service'));
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Disable failed'
        ];
    }

    /**
     * Create PPPoE secret for a new client
     * Package name = PPP profile name (CRITICAL for Bangladesh ISP model)
     * 
     * @param Client $client
     * @param string $password
     * @return array
     */
    public function createClientPPPoE(Client $client, string $password): array
    {
        $mikrotik = new SimpleMikroTikService();
        
        $pppoeUsername = $client->pppoe_username ?? $client->username;
        
        if (empty($pppoeUsername)) {
            return [
                'success' => false,
                'message' => 'Client has no PPPoE username'
            ];
        }

        if (!$client->package) {
            return [
                'success' => false,
                'message' => 'Client has no package assigned'
            ];
        }

        if (!$mikrotik->connectToDefault()) {
            return [
                'success' => false,
                'message' => 'Cannot connect to MikroTik router'
            ];
        }

        // CRITICAL: Package name = PPP profile name
        $profileName = $client->package->name;
        
        // VALIDATION: Verify profile exists on router BEFORE creating user
        if (!$mikrotik->profileExists($profileName)) {
            Log::error("Profile '{$profileName}' not found on router. Cannot create PPPoE user.");
            return [
                'success' => false,
                'message' => "Profile '{$profileName}' does not exist on MikroTik. Please create the profile first with matching speed limits."
            ];
        }
        
        $comment = "Client: {$client->client_id} - {$client->name}";

        $result = $mikrotik->createPPPoESecret($pppoeUsername, $password, $profileName, $comment, false);

        if ($result['success']) {
            Log::info("PPPoE secret created: {$pppoeUsername} with profile: {$profileName}");
        }

        return $result;
    }

    /**
     * Change client package on MikroTik (supports ALL connection types)
     * 
     * - PPPoE: Update PPP profile
     * - Hotspot: Delete old user, recreate with new profile
     * - Static IP: Update simple queue max-limit
     * - DHCP: Update simple queue max-limit
     * 
     * @param Client $client
     * @param string $newPackageName New package name (= new profile name)
     * @return array
     */
    public function changeClientPackage(Client $client, string $newPackageName): array
    {
        $mikrotik = new SimpleMikroTikService();
        $connectionType = strtolower($client->connection_type ?? 'pppoe');
        $username = $client->pppoe_username ?? $client->username;

        if (empty($username) && !in_array($connectionType, ['static', 'dhcp'])) {
            return [
                'success' => false,
                'message' => 'Client has no username configured'
            ];
        }

        $connected = false;
        $device = null;

        if ($client->mikrotik_device_id) {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if ($device && $device->is_active) {
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            }
        }

        if (!$connected && $client->zone_id) {
            $device = MikrotikDevice::where('zone_id', $client->zone_id)->where('is_active', true)->first();
            if ($device) {
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            }
        }

        if (!$connected) {
            $connected = $mikrotik->connectToDefault();
        }

        if (!$connected) {
            return [
                'success' => false,
                'message' => 'Cannot connect to MikroTik router'
            ];
        }

        $newPackage = \App\Models\Package::where('name', $newPackageName)->first();

        switch ($connectionType) {
            case 'pppoe':
                if (!$mikrotik->profileExists($newPackageName)) {
                    return [
                        'success' => false,
                        'message' => "Profile '{$newPackageName}' does not exist on MikroTik. Please create the profile first."
                    ];
                }
                $result = $mikrotik->updatePPPoEProfile($username, $newPackageName);
                break;

            case 'hotspot':
                $mikrotik->deleteHotspotUser($username);
                $password = $client->pppoe_password ?? '123456';
                $comment = "Client: {$client->client_id} - {$client->name}";
                $result = $mikrotik->createHotspotUser($username, $password, $newPackageName, $comment);
                break;

            case 'static':
            case 'static_ip':
                $queueName = $username ?: "client-{$client->id}";
                $maxLimit = $this->getSpeedFromPackage($newPackage);
                $result = $mikrotik->updateSimpleQueueLimit($queueName, $maxLimit);
                break;

            case 'dhcp':
                $queueName = $username ?: "client-{$client->id}";
                $maxLimit = $this->getSpeedFromPackage($newPackage);
                $result = $mikrotik->updateSimpleQueueLimit($queueName, $maxLimit);
                break;

            default:
                $result = ['success' => false, 'message' => "Unknown connection type: {$connectionType}"];
        }

        if ($result['success']) {
            Log::info("Package changed for {$username} ({$connectionType}) to: {$newPackageName}");
        }

        return $result;
    }

    /**
     * Delete client from MikroTik router (supports ALL connection types)
     * 
     * - PPPoE: deletePPPoESecret
     * - Hotspot: deleteHotspotUser
     * - Static: deleteSimpleQueue + removeFromAddressList
     * - DHCP: deleteDhcpLease + deleteSimpleQueue
     */
    public function deleteClientFromRouter(Client $client): array
    {
        $mikrotik = new SimpleMikroTikService();
        $connectionType = strtolower($client->connection_type ?? 'pppoe');
        $username = $client->pppoe_username ?? $client->username;

        $connected = false;
        $device = null;

        if ($client->mikrotik_device_id) {
            $device = MikrotikDevice::find($client->mikrotik_device_id);
            if ($device && $device->is_active) {
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            }
        }

        if (!$connected && $client->zone_id) {
            $device = MikrotikDevice::where('zone_id', $client->zone_id)->where('is_active', true)->first();
            if ($device) {
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            }
        }

        if (!$connected) {
            $connected = $mikrotik->connectToDefault();
        }

        if (!$connected) {
            return [
                'success' => false,
                'message' => 'Cannot connect to MikroTik router'
            ];
        }

        $queueName = $username ?: "client-{$client->id}";

        switch ($connectionType) {
            case 'pppoe':
                $result = $mikrotik->deletePPPoESecret($username);
                break;

            case 'hotspot':
                $result = $mikrotik->deleteHotspotUser($username);
                break;

            case 'static':
            case 'static_ip':
                $mikrotik->deleteSimpleQueue($queueName);
                if ($client->ip_address) {
                    $mikrotik->removeFromAddressList('allowed_clients', $client->ip_address);
                    $mikrotik->removeFromAddressList('disabled_clients', $client->ip_address);
                }
                $result = ['success' => true, 'message' => 'Static client removed from router'];
                break;

            case 'dhcp':
                if ($client->mac_address) {
                    $mikrotik->deleteDhcpLease($client->mac_address);
                }
                $mikrotik->deleteSimpleQueue($queueName);
                $result = ['success' => true, 'message' => 'DHCP client removed from router'];
                break;

            default:
                $result = ['success' => false, 'message' => "Unknown connection type: {$connectionType}"];
        }

        if ($result['success']) {
            Log::info("Client {$client->client_id} ({$username}) removed from router ({$connectionType})");
        }

        return $result;
    }

    protected function getSpeedFromPackage($package): string
    {
        if (!$package) {
            return '10M/10M';
        }

        $upload = $package->upload_speed ?? $package->bandwidth ?? 10;
        $download = $package->download_speed ?? $package->bandwidth ?? 10;
        $unit = $package->speed_unit ?? 'M';

        return "{$upload}{$unit}/{$download}{$unit}";
    }

    public function rebootOnu(Client $client): array
    {
        $result = [
            'success' => false,
            'message' => 'ONU reboot failed',
        ];

        if (!$client->olt_device_id || !$client->onu_serial) {
            $result['message'] = 'OLT/ONU not configured for this client';
            return $result;
        }

        $device = OltDevice::find($client->olt_device_id);
        if (!$device) {
            $result['message'] = 'OLT device not found';
            return $result;
        }

        try {
            $rebooted = $this->sendOnuRebootCommand($device, $client->olt_port, $client->onu_serial);
            
            if ($rebooted) {
                $result['success'] = true;
                $result['message'] = 'ONU reboot command sent successfully';
                
                Log::info("ONU reboot for client {$client->id}");
            }
        } catch (\Exception $e) {
            Log::error("ONU reboot error for client {$client->id}: " . $e->getMessage());
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Update client speed - DEPRECATED
     * 
     * IMPORTANT: In the Bangladesh ISP model, speed is controlled ONLY via PPP profiles.
     * To change speed, use changeClientPackage() to assign a new profile.
     * 
     * This method is kept for backward compatibility but does nothing.
     * Queue-based speed management is NOT used.
     * 
     * @deprecated Use changeClientPackage() instead
     */
    public function updateClientSpeed(Client $client, int $downloadMbps, int $uploadMbps): array
    {
        // Speed control via queues is NOT supported in Bangladesh ISP model
        // Speed is controlled ONLY via MikroTik PPP profiles
        
        Log::warning("updateClientSpeed called but speed control is via profiles only. Use changeClientPackage() instead.");
        
        return [
            'success' => false,
            'message' => 'Speed control is done via packages/profiles. Use package change to update speed.',
        ];
    }

    public function createPppoeUser(MikrotikDevice $device, array $userData): array
    {
        $result = [
            'success' => false,
            'message' => 'PPPoE user creation failed',
        ];

        try {
            $created = $this->createPppoeSecret($device, $userData);
            
            if ($created) {
                $result['success'] = true;
                $result['message'] = 'PPPoE user created successfully';
            }
        } catch (\Exception $e) {
            Log::error("PPPoE user creation error: " . $e->getMessage());
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    public function getAllActiveConnections(MikrotikDevice $device): array
    {
        try {
            return $this->getMikrotikActiveConnections($device);
        } catch (\Exception $e) {
            Log::error("Get active connections error: " . $e->getMessage());
            return [];
        }
    }

    public function getDeviceHealth(MikrotikDevice $device): array
    {
        try {
            return $this->getMikrotikHealth($device);
        } catch (\Exception $e) {
            Log::error("Get device health error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function calculateSignalQuality(?float $rxPower): ?string
    {
        if ($rxPower === null) return null;
        
        if ($rxPower >= -20) return 'excellent';
        if ($rxPower >= -25) return 'good';
        if ($rxPower >= -28) return 'fair';
        return 'poor';
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
        return $connected ? $mikrotik : null;
    }

    protected function getMikrotikPppoeSession($device, $username): ?array
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return null;
        }

        $session = $mikrotik->getPPPoESession($username);
        return $session ?: null;
    }

    protected function disconnectPppoeSession($device, $username): bool
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return false;
        }

        return $mikrotik->kickActiveSession($username);
    }

    protected function enablePppoeSecret($device, $username): bool
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return false;
        }

        $result = $mikrotik->enablePPPoESecret($username);
        return $result['success'] ?? false;
    }

    protected function disablePppoeSecret($device, $username): bool
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return false;
        }

        $result = $mikrotik->disablePPPoESecret($username);
        return $result['success'] ?? false;
    }

    protected function createPppoeSecret($device, array $userData): bool
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return false;
        }

        $result = $mikrotik->createPPPoESecret(
            $userData['name'] ?? $userData['username'] ?? '',
            $userData['password'] ?? '',
            $userData['profile'] ?? 'default',
            $userData['comment'] ?? '',
            $userData['disabled'] ?? false
        );
        return $result['success'] ?? false;
    }

    protected function updateQueueLimit($device, $username, $download, $upload): bool
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return false;
        }

        $maxLimit = "{$upload}M/{$download}M";
        $result = $mikrotik->updateSimpleQueueLimit($username, $maxLimit);
        return $result['success'] ?? false;
    }

    protected function getMikrotikActiveConnections($device): array
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return [];
        }

        return $mikrotik->getActivePPPSessions();
    }

    protected function getMikrotikHealth($device): array
    {
        $mikrotik = $this->connectToDevice($device);
        if (!$mikrotik) {
            return [
                'status' => 'offline',
                'cpu' => null,
                'memory' => null,
                'uptime' => null,
            ];
        }

        $resources = $mikrotik->getSystemResources();
        if (!$resources) {
            return [
                'status' => 'error',
                'cpu' => null,
                'memory' => null,
                'uptime' => null,
            ];
        }

        $totalMemory = (int)($resources['total-memory'] ?? 0);
        $freeMemory = (int)($resources['free-memory'] ?? 0);
        $memoryUsage = $totalMemory > 0 ? round(($totalMemory - $freeMemory) / $totalMemory * 100, 1) : null;

        return [
            'status' => 'online',
            'cpu' => $resources['cpu-load'] ?? null,
            'memory' => $memoryUsage,
            'uptime' => $resources['uptime'] ?? null,
            'board_name' => $resources['board-name'] ?? null,
            'version' => $resources['version'] ?? null,
            'architecture' => $resources['architecture-name'] ?? null,
            'total_memory' => $totalMemory,
            'free_memory' => $freeMemory,
        ];
    }

    protected function getOnuStatus($device, $onuSerial, $port): ?array
    {
        return null;
    }

    protected function sendOnuRebootCommand($device, $port, $onuSerial): bool
    {
        // Search ONU record — try linked OLT first, then same-IP OLTs
        $onuRecord = DB::table('onus')
            ->where('olt_device_id', $device->id)
            ->where(function($q) use ($onuSerial) {
                $q->where('serial_number', $onuSerial)
                  ->orWhere('mac_address', $onuSerial);
            })
            ->first();

        if (!$onuRecord) {
            $sameIpIds = DB::table('olt_devices')->where('ip_address', $device->ip_address)->pluck('id');
            $onuRecord = DB::table('onus')
                ->whereIn('olt_device_id', $sameIpIds)
                ->where(function($q) use ($onuSerial) {
                    $q->where('serial_number', $onuSerial)
                      ->orWhere('mac_address', $onuSerial);
                })
                ->first();
        }

        if (!$onuRecord) {
            Log::warning("ONU reboot: ONU record not found for serial {$onuSerial}");
            return false;
        }

        $ponPort   = $onuRecord->pon_port;
        $onuNumber = $onuRecord->onu_number;

        if (!$ponPort || !$onuNumber) {
            Log::warning("ONU reboot: Missing port or ONU number for serial {$onuSerial}");
            return false;
        }

        // Extract numeric port index (e.g. "EPON0/3" → "3")
        $portNum = '';
        if (preg_match('/EPON\d+\/(\d+)/i', $ponPort, $pm)) {
            $portNum = $pm[1];
        } elseif (preg_match('/GPON\d+\/(\d+)/i', $ponPort, $pm)) {
            $portNum = $pm[1];
        } elseif (preg_match('/(\d+)/', $ponPort, $pm)) {
            $portNum = $pm[1];
        }

        if ($portNum === '') {
            Log::warning("ONU reboot: Could not parse port number from '{$ponPort}'");
            return false;
        }

        // 1. Try SNMP reboot first (works for snmp connection_type OLTs)
        if ($device->snmp_enabled) {
            try {
                $oidMapping = \App\Services\OLT\OIDMapping::forVendor($device->vendor ?? 'generic', $device->pon_type ?? 'epon');
                $rebootOid  = $oidMapping->getRebootOid();

                if ($rebootOid) {
                    $snmpClient = \App\Services\OLT\SNMPClient::fromDevice($device);
                    $fullOid    = "{$rebootOid}.{$portNum}.{$onuNumber}";
                    $result     = $snmpClient->set($fullOid, 'i', 1);

                    if ($result) {
                        Log::info("ONU reboot success via SNMP: {$ponPort}:{$onuNumber} (serial: {$onuSerial})");
                        return true;
                    }
                    Log::warning("ONU reboot SNMP failed for {$onuSerial}: " . $snmpClient->getLastError() . ", trying CLI fallback");
                }
            } catch (\Exception $e) {
                Log::warning("ONU reboot SNMP exception: " . $e->getMessage() . ", trying CLI fallback");
            }
        }

        // 2. CLI fallback — use telnet/SSH OLT (same IP if current device is SNMP-only)
        $connType = $device->connection_type ?? 'telnet';
        $cliDevice = $device;

        if (!in_array($connType, ['telnet', 'ssh'])) {
            $altDevice = DB::table('olt_devices')
                ->where('ip_address', $device->ip_address)
                ->whereIn('connection_type', ['telnet', 'ssh'])
                ->first();

            if (!$altDevice) {
                Log::warning("ONU reboot: No telnet/SSH OLT found for {$device->ip_address} and SNMP reboot also failed");
                return false;
            }
            $cliDevice = $altDevice;
        }

        try {
            $api = OltService::fromDevice($cliDevice);
            if ($api->connect()) {
                $result = $api->rebootOnu($portNum, (string)$onuNumber);
                $api->disconnect();
                if ($result) {
                    Log::info("ONU reboot success via CLI: {$ponPort}:{$onuNumber} (serial: {$onuSerial})");
                    return true;
                }
                Log::error("ONU reboot: CLI command failed for {$ponPort}:{$onuNumber}");
                return false;
            }
            Log::error("ONU reboot: Failed to connect to OLT {$cliDevice->ip_address} for CLI");
            return false;
        } catch (\Exception $e) {
            Log::error("ONU reboot CLI error: " . $e->getMessage());
            return false;
        }
    }

    protected function logRouterAction(?Client $client, ?MikrotikDevice $device, string $action, string $actionType, string $status, ?string $errorMessage = null): void
    {
        try {
            DB::table('mikrotik_action_logs')->insert([
                'client_id' => $client?->id,
                'device_id' => $device?->id,
                'action' => $action,
                'action_type' => $actionType,
                'username' => $client?->username ?? $client?->pppoe_username,
                'router_ip' => $device?->ip_address ?? $device?->host,
                'trigger_source' => 'device_control',
                'triggered_by' => auth()->id(),
                'status' => $status,
                'error_message' => $errorMessage,
                'request_data' => json_encode(['action' => $action, 'connection_type' => $actionType]),
                'response_data' => json_encode(['status' => $status]),
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log router action: " . $e->getMessage());
        }
    }
}
