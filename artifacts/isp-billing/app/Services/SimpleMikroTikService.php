<?php
/**
 * SimpleMikroTikService - Production-ready MikroTik API for Bangladesh ISPs
 * 
 * SUPPORTS ALL CONNECTION TYPES:
 * - PPPoE (most common in Bangladesh)
 * - Hotspot (cybercafe, hotel WiFi)
 * - Static IP (business clients)
 * - DHCP (dynamic IP assignment)
 * 
 * BUSINESS RULES:
 * - Speed control via MikroTik profiles (PPP profiles for PPPoE, Hotspot profiles for Hotspot)
 * - Package name in billing MUST exactly match MikroTik profile name
 * - Queue management available for Static IP/DHCP clients
 * - Single or multi-router model supported
 * 
 * CONNECTION TYPES:
 * - pppoe: Uses /ppp/secret with PPP profiles
 * - hotspot: Uses /ip/hotspot/user with Hotspot profiles
 * - static: Uses /ip/firewall/address-list + /queue/simple
 * - dhcp: Uses /ip/dhcp-server/lease + /queue/simple
 * 
 * USAGE:
 * $mikrotik = new SimpleMikroTikService();
 * $mikrotik->connect('10.10.10.1', 'admin', 'password', 8728);
 * $mikrotik->createPPPoESecret('username', 'password', 'BASIC'); // PPPoE
 * $mikrotik->createHotspotUser('username', 'password', 'BASIC'); // Hotspot
 * $mikrotik->createStaticBinding('192.168.1.100', 'BASIC', 'Client Name'); // Static IP
 */

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\MikrotikActionLog;
use Illuminate\Support\Facades\Log;

class SimpleMikroTikService
{
    protected $socket = null;
    protected $host;
    protected $user;
    protected $pass;
    protected $port;
    protected bool $connected = false;
    protected ?MikrotikDevice $device = null;

    /**
     * Connect to MikroTik router
     * 
     * @param string $host Router IP (e.g., 10.10.10.1)
     * @param string $user API username
     * @param string $pass API password
     * @param int $port API port (default 8728, NO SSL)
     * @return bool
     */
    public function connect(string $host, string $user, string $pass, int $port = 8728, int $timeout = 10): bool
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;

        if ($this->connected) {
            return true;
        }

        try {
            $this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
            
            if (!$this->socket) {
                $this->logError("Connection failed to {$host}:{$port} - {$errstr} ({$errno})");
                return false;
            }

            stream_set_timeout($this->socket, $timeout + 5);

            if (!$this->login()) {
                fclose($this->socket);
                $this->socket = null;
                $this->logError("Login failed for user {$user} on {$host}");
                return false;
            }

            $this->connected = true;
            $this->logInfo("Connected to MikroTik {$host}:{$port}");
            return true;

        } catch (\Exception $e) {
            $this->logError("Connection exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Connect using a MikrotikDevice model
     */
    public function connectFromDevice(MikrotikDevice $device): bool
    {
        $this->device = $device;
        $host = $device->getRawOriginal('host') ?? $device->host;
        $port = (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
        
        return $this->connect($host, $device->username, $device->password, $port);
    }

    /**
     * Get the first active router (single router model)
     */
    public function getDefaultRouter(): ?MikrotikDevice
    {
        return MikrotikDevice::where('is_active', true)->first();
    }

    /**
     * Connect to default router automatically
     */
    public function connectToDefault(): bool
    {
        $device = $this->getDefaultRouter();
        if (!$device) {
            $this->logError("No active MikroTik device found in database");
            return false;
        }
        return $this->connectFromDevice($device);
    }

    // ==========================================
    // PPPoE SECRET MANAGEMENT
    // ==========================================

    /**
     * Create a new PPPoE secret (user)
     * 
     * @param string $username PPPoE username
     * @param string $password PPPoE password
     * @param string $profile MikroTik PPP profile name (MUST match exactly!)
     * @param string $comment Optional comment (client info)
     * @param bool $disabled Create as disabled (default: no)
     * @return array ['success' => bool, 'message' => string, 'id' => string|null]
     */
    public function createPPPoESecret(string $username, string $password, string $profile, string $comment = '', bool $disabled = false): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        // Check if user already exists
        $existing = $this->findPPPoESecret($username);
        if ($existing) {
            return $this->errorResponse("PPPoE user '{$username}' already exists");
        }

        $params = [
            'name' => $username,
            'password' => $password,
            'service' => 'pppoe',
            'profile' => $profile,  // THIS IS THE BANDWIDTH PROFILE - MUST MATCH EXACTLY!
        ];

        if (!empty($comment)) {
            $params['comment'] = $comment;
        }

        if ($disabled) {
            $params['disabled'] = 'yes';
        }

        $response = $this->sendCommand('/ppp/secret/add', $params);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("PPPoE secret created: {$username} with profile: {$profile}");
            return $this->successResponse("PPPoE user created successfully");
        }

        $error = $this->extractError($response);
        $this->logError("Failed to create PPPoE secret {$username}: {$error}");
        return $this->errorResponse($error);
    }

    /**
     * Enable a PPPoE secret (activate client connection)
     * 
     * @param string $username PPPoE username
     * @return array
     */
    public function enablePPPoESecret(string $username): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $secret = $this->findPPPoESecret($username);
        if (!$secret) {
            return $this->errorResponse("PPPoE user '{$username}' not found on router");
        }

        $id = $secret['.id'] ?? null;
        if (!$id) {
            return $this->errorResponse("Could not get ID for user '{$username}'");
        }

        $response = $this->sendCommand('/ppp/secret/enable', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("PPPoE secret enabled: {$username}");
            return $this->successResponse("PPPoE user enabled successfully");
        }

        $error = $this->extractError($response);
        $this->logError("Failed to enable PPPoE secret {$username}: {$error}");
        return $this->errorResponse($error);
    }

    /**
     * Disable a PPPoE secret (suspend client connection)
     * Also disconnects active session
     * 
     * @param string $username PPPoE username
     * @return array
     */
    public function disablePPPoESecret(string $username): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        // First disconnect if online
        $this->kickActiveSession($username);

        $secret = $this->findPPPoESecret($username);
        if (!$secret) {
            return $this->errorResponse("PPPoE user '{$username}' not found on router");
        }

        $id = $secret['.id'] ?? null;
        if (!$id) {
            return $this->errorResponse("Could not get ID for user '{$username}'");
        }

        $response = $this->sendCommand('/ppp/secret/disable', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("PPPoE secret disabled: {$username}");
            return $this->successResponse("PPPoE user disabled successfully");
        }

        $error = $this->extractError($response);
        $this->logError("Failed to disable PPPoE secret {$username}: {$error}");
        return $this->errorResponse($error);
    }

    /**
     * Update PPPoE secret profile (change package/speed)
     * 
     * IMPORTANT: Package name = Profile name
     * When client changes from "BASIC" to "STANDARD" package,
     * this updates their PPP profile to "STANDARD"
     * 
     * @param string $username PPPoE username
     * @param string $newProfile New profile name (= new package name)
     * @return array
     */
    public function updatePPPoEProfile(string $username, string $newProfile): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $secret = $this->findPPPoESecret($username);
        if (!$secret) {
            return $this->errorResponse("PPPoE user '{$username}' not found on router");
        }

        $id = $secret['.id'] ?? null;
        if (!$id) {
            return $this->errorResponse("Could not get ID for user '{$username}'");
        }

        // Kick current session to apply new profile
        $this->kickActiveSession($username);

        $response = $this->sendCommand('/ppp/secret/set', [
            '.id' => $id,
            'profile' => $newProfile
        ]);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("PPPoE profile updated: {$username} -> {$newProfile}");
            return $this->successResponse("Profile updated to {$newProfile}");
        }

        $error = $this->extractError($response);
        $this->logError("Failed to update profile for {$username}: {$error}");
        return $this->errorResponse($error);
    }

    /**
     * Delete a PPPoE secret
     * 
     * @param string $username PPPoE username
     * @return array
     */
    public function deletePPPoESecret(string $username): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        // Disconnect first
        $this->kickActiveSession($username);

        $secret = $this->findPPPoESecret($username);
        if (!$secret) {
            return $this->successResponse("User already does not exist"); // Not an error
        }

        $id = $secret['.id'] ?? null;
        if (!$id) {
            return $this->errorResponse("Could not get ID for user '{$username}'");
        }

        $response = $this->sendCommand('/ppp/secret/remove', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("PPPoE secret deleted: {$username}");
            return $this->successResponse("PPPoE user deleted successfully");
        }

        $error = $this->extractError($response);
        $this->logError("Failed to delete PPPoE secret {$username}: {$error}");
        return $this->errorResponse($error);
    }

    /**
     * Find a PPPoE secret by username
     * 
     * @param string $username
     * @return array|null
     */
    public function findPPPoESecret(string $username): ?array
    {
        $response = $this->sendCommand('/ppp/secret/print', ['?name' => $username]);
        $secrets = $this->parseResponse($response);
        return $secrets[0] ?? null;
    }

    /**
     * Check if a PPPoE user exists on the router
     * 
     * @param string $username
     * @return bool
     */
    public function pppoeUserExists(string $username): bool
    {
        return $this->findPPPoESecret($username) !== null;
    }

    public function getPPPoEUserProfile(string $username): ?string
    {
        $secret = $this->findPPPoESecret($username);
        if (!$secret) {
            return null;
        }
        return $secret['profile'] ?? null;
    }

    public function changePPPoEProfile(string $username, string $newProfile): bool
    {
        $result = $this->updatePPPoEProfile($username, $newProfile);
        return $result['success'] ?? false;
    }

    // ==========================================
    // ACTIVE SESSION MANAGEMENT
    // ==========================================

    /**
     * Get list of all active PPPoE sessions
     * 
     * @return array
     */
    public function getActiveSessions(): array
    {
        if (!$this->ensureConnected()) {
            return [];
        }

        $response = $this->sendCommand('/ppp/active/print');
        return $this->parseResponse($response);
    }

    /**
     * Check if a user is currently online
     * 
     * @param string $username
     * @return bool
     */
    public function isUserOnline(string $username): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        $response = $this->sendCommand('/ppp/active/print', ['?name' => $username]);
        $sessions = $this->parseResponse($response);
        return !empty($sessions);
    }

    /**
     * Get active session details for a user
     * 
     * @param string $username
     * @return array|null
     */
    public function getActiveSession(string $username): ?array
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        $response = $this->sendCommand('/ppp/active/print');
        $sessions = $this->parseResponse($response);

        foreach ($sessions as $session) {
            if (isset($session['name']) && $session['name'] === $username) {
                return $session;
            }
        }

        return null;
    }

    /**
     * Get PPPoE session with bandwidth info (alias for getActiveSession)
     * Used for live bandwidth monitoring
     * 
     * @param string $username
     * @return array|null
     */
    public function getPPPoESession(string $username): ?array
    {
        $session = $this->getActiveSession($username);
        
        if (!$session || !$this->ensureConnected()) {
            return $session;
        }

        // Method 1: Try to get cumulative bytes from the PPPoE interface counter
        // MikroTik PPPoE interface is named <pppoe-username>
        $interfaceName = '<pppoe-' . $username . '>';
        $response = $this->sendCommand('/interface/print', [
            '?name' => $interfaceName,
            '.proplist' => '.id,name,tx-byte,rx-byte,tx-packet,rx-packet',
        ]);
        $interfaces = $this->parseResponse($response);
        
        if (!empty($interfaces[0])) {
            $iface = $interfaces[0];
            // Interface TX = bytes sent to client (client's download / rx)
            // Interface RX = bytes received from client (client's upload / tx)
            $session['tx-bytes'] = $iface['tx-byte'] ?? 0;
            $session['rx-bytes'] = $iface['rx-byte'] ?? 0;
        }

        // Method 2: Fallback — fetch bytes-in/bytes-out directly from active session
        // /ppp/active/print includes bytes-in (client upload) and bytes-out (client download)
        if (empty($session['tx-bytes']) && empty($session['rx-bytes'])) {
            $bytesResponse = $this->sendCommand('/ppp/active/print', [
                '?name' => $username,
                '.proplist' => '.id,name,bytes-in,bytes-out',
            ]);
            $byteSessions = $this->parseResponse($bytesResponse);
            foreach ($byteSessions as $bs) {
                if (isset($bs['name']) && $bs['name'] === $username) {
                    // bytes-out = bytes sent to client = client download = tx-bytes
                    // bytes-in  = bytes received from client = client upload = rx-bytes
                    $session['tx-bytes'] = $bs['bytes-out'] ?? 0;
                    $session['rx-bytes'] = $bs['bytes-in']  ?? 0;
                    break;
                }
            }
        }

        return $session;
    }

    /**
     * Get Hotspot active session with bandwidth info
     * 
     * @param string $username
     * @return array|null
     */
    public function getHotspotSession(string $username): ?array
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        $response = $this->sendCommand('/ip/hotspot/active/print', ['?user' => $username]);
        $sessions = $this->parseResponse($response);
        return $sessions[0] ?? null;
    }

    /**
     * Get Simple Queue statistics (for Static/DHCP bandwidth monitoring)
     * 
     * @param string $name Queue name
     * @return array|null
     */
    public function getSimpleQueueStats(string $name): ?array
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        $response = $this->sendCommand('/queue/simple/print', ['?name' => $name]);
        $queues = $this->parseResponse($response);
        return $queues[0] ?? null;
    }

    /**
     * Get Simple Queue by target IP (for Static/DHCP clients)
     * 
     * @param string $targetIp
     * @return array|null
     */
    public function getSimpleQueueByTarget(string $targetIp): ?array
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        $response = $this->sendCommand('/queue/simple/print', ['?target' => $targetIp . '/32']);
        $queues = $this->parseResponse($response);
        
        if (empty($queues)) {
            // Try without subnet mask
            $response = $this->sendCommand('/queue/simple/print', ['?target' => $targetIp]);
            $queues = $this->parseResponse($response);
        }
        
        return $queues[0] ?? null;
    }

    /**
     * Kick (disconnect) an active PPPoE session
     * 
     * @param string $username
     * @return bool
     */
    public function kickActiveSession(string $username): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        $response = $this->sendCommand('/ppp/active/print', ['?name' => $username]);
        $sessions = $this->parseResponse($response);
        
        if (empty($sessions)) {
            return true; // Already offline
        }

        $id = $sessions[0]['.id'] ?? null;
        if ($id) {
            $this->sendCommand('/ppp/active/remove', ['.id' => $id]);
            $this->logInfo("Active session kicked: {$username}");
            return true;
        }
        
        return false;
    }

    // ==========================================
    // PPP PROFILES (read-only - managed in RouterOS)
    // ==========================================

    /**
     * Get list of all PPP profiles from router
     * Used to verify package names match profile names
     * 
     * @return array
     */
    public function getProfiles(): array
    {
        if (!$this->ensureConnected()) {
            return [];
        }

        $response = $this->sendCommand('/ppp/profile/print');
        return $this->parseResponse($response);
    }

    /**
     * Check if a profile exists on the router
     * 
     * @param string $profileName
     * @return bool
     */
    public function profileExists(string $profileName): bool
    {
        $profiles = $this->getProfiles();
        foreach ($profiles as $profile) {
            if (($profile['name'] ?? '') === $profileName) {
                return true;
            }
        }
        return false;
    }

    // ==========================================
    // HOTSPOT USER MANAGEMENT
    // ==========================================

    /**
     * Create a new Hotspot user
     * 
     * @param string $username Hotspot username
     * @param string $password Hotspot password
     * @param string $profile MikroTik Hotspot profile name
     * @param string $comment Optional comment
     * @param bool $disabled Create as disabled
     * @return array
     */
    public function createHotspotUser(string $username, string $password, string $profile, string $comment = '', bool $disabled = false): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        // Check if user already exists
        $existing = $this->findHotspotUser($username);
        if ($existing) {
            return $this->errorResponse("Hotspot user '{$username}' already exists");
        }

        $params = [
            'name' => $username,
            'password' => $password,
            'profile' => $profile,
            'server' => 'all',
        ];

        if (!empty($comment)) {
            $params['comment'] = $comment;
        }

        if ($disabled) {
            $params['disabled'] = 'yes';
        }

        $response = $this->sendCommand('/ip/hotspot/user/add', $params);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("Hotspot user created: {$username} with profile: {$profile}");
            return $this->successResponse("Hotspot user created successfully");
        }

        $error = $this->extractError($response);
        $this->logError("Failed to create Hotspot user {$username}: {$error}");
        return $this->errorResponse($error);
    }

    /**
     * Find Hotspot user by username
     */
    public function findHotspotUser(string $username): ?array
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        $response = $this->sendCommand('/ip/hotspot/user/print', ['?name' => $username]);
        $users = $this->parseResponse($response);
        return $users[0] ?? null;
    }

    /**
     * Enable Hotspot user
     */
    public function enableHotspotUser(string $username): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $user = $this->findHotspotUser($username);
        if (!$user) {
            return $this->errorResponse("Hotspot user '{$username}' not found");
        }

        $id = $user['.id'] ?? null;
        if (!$id) {
            return $this->errorResponse("Could not get ID for user '{$username}'");
        }

        $response = $this->sendCommand('/ip/hotspot/user/enable', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("Hotspot user enabled: {$username}");
            return $this->successResponse("Hotspot user enabled successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Disable Hotspot user
     */
    public function disableHotspotUser(string $username): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $user = $this->findHotspotUser($username);
        if (!$user) {
            return $this->errorResponse("Hotspot user '{$username}' not found");
        }

        $id = $user['.id'] ?? null;
        $response = $this->sendCommand('/ip/hotspot/user/disable', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("Hotspot user disabled: {$username}");
            return $this->successResponse("Hotspot user disabled successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Delete Hotspot user
     */
    public function deleteHotspotUser(string $username): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $user = $this->findHotspotUser($username);
        if (!$user) {
            return $this->successResponse("User already removed or not found");
        }

        $id = $user['.id'] ?? null;
        $response = $this->sendCommand('/ip/hotspot/user/remove', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("Hotspot user deleted: {$username}");
            return $this->successResponse("Hotspot user deleted successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Kick active Hotspot session
     */
    public function kickHotspotSession(string $username): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $response = $this->sendCommand('/ip/hotspot/active/print', ['?user' => $username]);
        $sessions = $this->parseResponse($response);
        
        foreach ($sessions as $session) {
            $id = $session['.id'] ?? null;
            if ($id) {
                $this->sendCommand('/ip/hotspot/active/remove', ['.id' => $id]);
            }
        }

        return $this->successResponse("Hotspot session kicked");
    }

    /**
     * Get all Hotspot profiles
     */
    public function getHotspotProfiles(): array
    {
        if (!$this->ensureConnected()) {
            return [];
        }

        $response = $this->sendCommand('/ip/hotspot/user/profile/print');
        return $this->parseResponse($response);
    }

    /**
     * Check if Hotspot profile exists
     */
    public function hotspotProfileExists(string $profileName): bool
    {
        $profiles = $this->getHotspotProfiles();
        foreach ($profiles as $profile) {
            if (($profile['name'] ?? '') === $profileName) {
                return true;
            }
        }
        return false;
    }

    // ==========================================
    // STATIC IP / DHCP MANAGEMENT
    // ==========================================

    /**
     * Create Simple Queue for Static IP or DHCP client
     * Speed control for non-PPPoE clients
     * 
     * @param string $name Queue name (usually client username)
     * @param string $target Target IP address
     * @param string $maxLimit Speed limit (e.g., "10M/20M" = upload/download)
     * @param string $comment Optional comment
     * @return array
     */
    public function createSimpleQueue(string $name, string $target, string $maxLimit, string $comment = ''): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        // Check if queue already exists
        $existing = $this->findSimpleQueue($name);
        if ($existing) {
            return $this->errorResponse("Queue '{$name}' already exists");
        }

        $params = [
            'name' => $name,
            'target' => $target,
            'max-limit' => $maxLimit,
        ];

        if (!empty($comment)) {
            $params['comment'] = $comment;
        }

        $response = $this->sendCommand('/queue/simple/add', $params);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("Simple queue created: {$name} for {$target} with limit: {$maxLimit}");
            return $this->successResponse("Queue created successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Find Simple Queue by name
     */
    public function findSimpleQueue(string $name): ?array
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        $response = $this->sendCommand('/queue/simple/print', ['?name' => $name]);
        $queues = $this->parseResponse($response);
        return $queues[0] ?? null;
    }

    /**
     * Update Simple Queue speed limit
     */
    public function updateSimpleQueueLimit(string $name, string $maxLimit): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $queue = $this->findSimpleQueue($name);
        if (!$queue) {
            return $this->errorResponse("Queue '{$name}' not found");
        }

        $id = $queue['.id'] ?? null;
        $response = $this->sendCommand('/queue/simple/set', [
            '.id' => $id,
            'max-limit' => $maxLimit,
        ]);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("Queue limit updated: {$name} to {$maxLimit}");
            return $this->successResponse("Queue limit updated successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Enable Simple Queue
     */
    public function enableSimpleQueue(string $name): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $queue = $this->findSimpleQueue($name);
        if (!$queue) {
            return $this->errorResponse("Queue '{$name}' not found");
        }

        $id = $queue['.id'] ?? null;
        $response = $this->sendCommand('/queue/simple/enable', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            return $this->successResponse("Queue enabled successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Disable Simple Queue
     */
    public function disableSimpleQueue(string $name): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $queue = $this->findSimpleQueue($name);
        if (!$queue) {
            return $this->errorResponse("Queue '{$name}' not found");
        }

        $id = $queue['.id'] ?? null;
        $response = $this->sendCommand('/queue/simple/disable', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            return $this->successResponse("Queue disabled successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Delete Simple Queue
     */
    public function deleteSimpleQueue(string $name): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $queue = $this->findSimpleQueue($name);
        if (!$queue) {
            return $this->successResponse("Queue already removed");
        }

        $id = $queue['.id'] ?? null;
        $response = $this->sendCommand('/queue/simple/remove', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            return $this->successResponse("Queue deleted successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Create DHCP static lease
     */
    public function createDhcpLease(string $macAddress, string $ipAddress, string $server = 'dhcp1', string $comment = ''): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $params = [
            'mac-address' => $macAddress,
            'address' => $ipAddress,
            'server' => $server,
        ];

        if (!empty($comment)) {
            $params['comment'] = $comment;
        }

        $response = $this->sendCommand('/ip/dhcp-server/lease/add', $params);
        
        if ($this->isSuccess($response)) {
            $this->logInfo("DHCP lease created: {$macAddress} -> {$ipAddress}");
            return $this->successResponse("DHCP lease created successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Find DHCP lease by MAC address
     */
    public function findDhcpLease(string $macAddress): ?array
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        $response = $this->sendCommand('/ip/dhcp-server/lease/print', ['?mac-address' => $macAddress]);
        $leases = $this->parseResponse($response);
        return $leases[0] ?? null;
    }

    /**
     * Delete DHCP lease
     */
    public function deleteDhcpLease(string $macAddress): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $lease = $this->findDhcpLease($macAddress);
        if (!$lease) {
            return $this->successResponse("Lease already removed");
        }

        $id = $lease['.id'] ?? null;
        $response = $this->sendCommand('/ip/dhcp-server/lease/remove', ['.id' => $id]);
        
        if ($this->isSuccess($response)) {
            return $this->successResponse("DHCP lease deleted successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Add IP to address list (for Static IP clients)
     */
    public function addToAddressList(string $list, string $address, string $comment = ''): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $params = [
            'list' => $list,
            'address' => $address,
        ];

        if (!empty($comment)) {
            $params['comment'] = $comment;
        }

        $response = $this->sendCommand('/ip/firewall/address-list/add', $params);
        
        if ($this->isSuccess($response)) {
            return $this->successResponse("Address added to list successfully");
        }

        return $this->errorResponse($this->extractError($response));
    }

    /**
     * Remove IP from address list
     */
    public function removeFromAddressList(string $list, string $address): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Not connected to router");
        }

        $response = $this->sendCommand('/ip/firewall/address-list/print', [
            '?list' => $list,
            '?address' => $address,
        ]);
        $entries = $this->parseResponse($response);
        
        foreach ($entries as $entry) {
            $id = $entry['.id'] ?? null;
            if ($id) {
                $this->sendCommand('/ip/firewall/address-list/remove', ['.id' => $id]);
            }
        }

        return $this->successResponse("Address removed from list");
    }

    // ==========================================
    // UNIVERSAL CLIENT OPERATIONS
    // ==========================================

    /**
     * Enable client based on connection type
     * 
     * @param Client $client
     * @return array
     */
    public function enableClientByType(Client $client): array
    {
        $connectionType = $client->connection_type ?? 'pppoe';
        $username = $client->pppoe_username ?? $client->username;

        if (!$this->ensureConnected()) {
            return $this->errorResponse("Cannot connect to router");
        }

        switch ($connectionType) {
            case 'pppoe':
                return $this->enablePPPoESecret($username);
            case 'hotspot':
                return $this->enableHotspotUser($username);
            case 'static':
            case 'dhcp':
                $queueName = $username ?: "client-{$client->id}";
                return $this->enableSimpleQueue($queueName);
            default:
                return $this->errorResponse("Unknown connection type: {$connectionType}");
        }
    }

    /**
     * Disable client based on connection type
     * 
     * @param Client $client
     * @return array
     */
    public function disableClientByType(Client $client): array
    {
        $connectionType = $client->connection_type ?? 'pppoe';
        $username = $client->pppoe_username ?? $client->username;

        if (!$this->ensureConnected()) {
            return $this->errorResponse("Cannot connect to router");
        }

        switch ($connectionType) {
            case 'pppoe':
                return $this->disablePPPoESecret($username);
            case 'hotspot':
                $this->kickHotspotSession($username);
                return $this->disableHotspotUser($username);
            case 'static':
            case 'dhcp':
                $queueName = $username ?: "client-{$client->id}";
                return $this->disableSimpleQueue($queueName);
            default:
                return $this->errorResponse("Unknown connection type: {$connectionType}");
        }
    }

    /**
     * Create client on MikroTik based on connection type
     */
    public function createClientByType(Client $client, string $password): array
    {
        $connectionType = $client->connection_type ?? 'pppoe';
        $username = $client->pppoe_username ?? $client->username;
        $profileName = $client->mikrotik_profile_name ?: ($client->package?->mikrotik_profile ?: ($client->package?->code ?: ($client->package?->name ?? 'default')));
        $comment = "Client: {$client->client_id} - {$client->name}";

        if (!$this->ensureConnected()) {
            return $this->errorResponse("Cannot connect to router");
        }

        switch ($connectionType) {
            case 'pppoe':
                return $this->createPPPoESecret($username, $password, $profileName, $comment);
            case 'hotspot':
                return $this->createHotspotUser($username, $password, $profileName, $comment);
            case 'static':
                // For static IP, create a simple queue with the IP
                $ipAddress = $client->ip_address;
                if (empty($ipAddress)) {
                    return $this->errorResponse("Static IP client requires IP address");
                }
                $maxLimit = $this->getSpeedFromPackage($client->package);
                $queueName = $username ?: "client-{$client->id}";
                return $this->createSimpleQueue($queueName, $ipAddress, $maxLimit, $comment);
            case 'dhcp':
                // For DHCP, create lease and queue
                $macAddress = $client->mac_address;
                $ipAddress = $client->ip_address;
                if (empty($macAddress)) {
                    return $this->errorResponse("DHCP client requires MAC address");
                }
                if (!empty($ipAddress)) {
                    $this->createDhcpLease($macAddress, $ipAddress, 'dhcp1', $comment);
                }
                $maxLimit = $this->getSpeedFromPackage($client->package);
                $queueName = $username ?: "client-{$client->id}";
                return $this->createSimpleQueue($queueName, $ipAddress ?: $macAddress, $maxLimit, $comment);
            default:
                return $this->errorResponse("Unknown connection type: {$connectionType}");
        }
    }

    /**
     * Get speed limit string from package
     */
    protected function getSpeedFromPackage($package): string
    {
        if (!$package) {
            return '10M/10M'; // Default 10Mbps
        }
        
        $upload = $package->upload_speed ?? $package->bandwidth ?? 10;
        $download = $package->download_speed ?? $package->bandwidth ?? 10;
        $unit = $package->speed_unit ?? 'M';
        
        return "{$upload}{$unit}/{$download}{$unit}";
    }

    // ==========================================
    // CLIENT INTEGRATION HELPERS
    // ==========================================

    /**
     * Enable client connection on MikroTik
     * This is called when client pays and needs to be activated
     * 
     * @param Client $client
     * @return array
     */
    public function enableClient(Client $client): array
    {
        if (empty($client->pppoe_username)) {
            return $this->errorResponse("Client has no PPPoE username");
        }

        // Auto-connect to router if not connected
        if (!$this->connected) {
            if (!$this->connectToDefault()) {
                return $this->errorResponse("Cannot connect to router");
            }
        }

        $result = $this->enablePPPoESecret($client->pppoe_username);
        
        if ($result['success']) {
            // Log the action
            $this->logMikrotikAction($client, 'enable', 'success');
        } else {
            $this->logMikrotikAction($client, 'enable', 'failed', $result['message']);
        }

        return $result;
    }

    /**
     * Disable client connection on MikroTik
     * This is called when client is overdue and needs to be suspended
     * 
     * @param Client $client
     * @return array
     */
    public function disableClient(Client $client): array
    {
        if (empty($client->pppoe_username)) {
            return $this->errorResponse("Client has no PPPoE username");
        }

        // Auto-connect to router if not connected
        if (!$this->connected) {
            if (!$this->connectToDefault()) {
                return $this->errorResponse("Cannot connect to router");
            }
        }

        $result = $this->disablePPPoESecret($client->pppoe_username);
        
        if ($result['success']) {
            $this->logMikrotikAction($client, 'disable', 'success');
        } else {
            $this->logMikrotikAction($client, 'disable', 'failed', $result['message']);
        }

        return $result;
    }

    /**
     * Create PPPoE secret for a new client
     * 
     * @param Client $client
     * @param string $password PPPoE password
     * @return array
     */
    public function createClientSecret(Client $client, string $password): array
    {
        if (empty($client->pppoe_username)) {
            return $this->errorResponse("Client has no PPPoE username");
        }

        if (!$client->package) {
            return $this->errorResponse("Client has no package assigned");
        }

        // Auto-connect to router if not connected
        if (!$this->connected) {
            if (!$this->connectToDefault()) {
                return $this->errorResponse("Cannot connect to router");
            }
        }

        // Package name = Profile name (CRITICAL!)
        $profileName = $client->package->name;

        // Check if profile exists on router
        if (!$this->profileExists($profileName)) {
            $this->logError("Profile '{$profileName}' does not exist on router! Create it first.");
            return $this->errorResponse("Profile '{$profileName}' not found on router. Please create it in MikroTik first.");
        }

        $comment = "Client: {$client->client_id} - {$client->name}";
        
        $result = $this->createPPPoESecret(
            $client->pppoe_username,
            $password,
            $profileName,
            $comment,
            false // Create as enabled
        );

        if ($result['success']) {
            $this->logMikrotikAction($client, 'create', 'success');
        } else {
            $this->logMikrotikAction($client, 'create', 'failed', $result['message']);
        }

        return $result;
    }

    /**
     * Update client's package (change profile on MikroTik)
     * 
     * @param Client $client
     * @param string $newPackageName New package name (= new profile name)
     * @return array
     */
    public function changeClientPackage(Client $client, string $newPackageName): array
    {
        if (empty($client->pppoe_username)) {
            return $this->errorResponse("Client has no PPPoE username");
        }

        // Auto-connect to router if not connected
        if (!$this->connected) {
            if (!$this->connectToDefault()) {
                return $this->errorResponse("Cannot connect to router");
            }
        }

        // Check if new profile exists
        if (!$this->profileExists($newPackageName)) {
            return $this->errorResponse("Profile '{$newPackageName}' not found on router. Please create it in MikroTik first.");
        }

        $result = $this->updatePPPoEProfile($client->pppoe_username, $newPackageName);

        if ($result['success']) {
            $this->logMikrotikAction($client, 'change_package', 'success', "Changed to: {$newPackageName}");
        } else {
            $this->logMikrotikAction($client, 'change_package', 'failed', $result['message']);
        }

        return $result;
    }

    // ==========================================
    // ACTIVE SESSIONS
    // ==========================================

    public function getActivePPPSessions(): array
    {
        if (!$this->ensureConnected()) {
            return [];
        }

        $response = $this->sendCommand('/ppp/active/print');
        return $this->parseResponse($response);
    }

    public function getActiveHotspotSessions(): array
    {
        if (!$this->ensureConnected()) {
            return [];
        }

        $response = $this->sendCommand('/ip/hotspot/active/print');
        return $this->parseResponse($response);
    }

    // ==========================================
    // ROUTER INFO
    // ==========================================

    /**
     * Get router system resources
     */
    public function getSystemResources(): array
    {
        if (!$this->ensureConnected()) {
            return [];
        }

        $response = $this->sendCommand('/system/resource/print');
        $data = $this->parseResponse($response);
        return $data[0] ?? [];
    }

    public function getSystemIdentity(): ?string
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        try {
            $response = $this->sendCommand('/system/identity/print');
            $data = $this->parseResponse($response);
            return $data[0]['name'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Test connection to router
     */
    public function testConnection(): array
    {
        if (!$this->ensureConnected()) {
            return $this->errorResponse("Cannot connect to router");
        }

        $resources = $this->getSystemResources();
        if (!empty($resources)) {
            return $this->successResponse("Connected successfully", [
                'router_name' => $resources['board-name'] ?? 'Unknown',
                'version' => $resources['version'] ?? 'Unknown',
                'uptime' => $resources['uptime'] ?? 'Unknown',
            ]);
        }

        return $this->errorResponse("Connected but cannot read router info");
    }

    // ==========================================
    // INTERNAL METHODS
    // ==========================================

    protected function ensureConnected(): bool
    {
        if ($this->connected) {
            return true;
        }
        
        // Try to auto-connect
        return $this->connectToDefault();
    }

    protected function login(): bool
    {
        $response = $this->sendCommand('/login', [
            'name' => $this->user,
            'password' => $this->pass
        ]);

        if (in_array('!done', $response)) {
            return true;
        }

        $challenge = null;
        foreach ($response as $line) {
            if (str_starts_with($line, '=ret=')) {
                $challenge = substr($line, 5);
                break;
            }
        }

        if ($challenge) {
            $challengeBin = hex2bin($challenge);
            $hash = md5(chr(0) . $this->pass . $challengeBin, true);
            $hexHash = '00' . bin2hex($hash);
            $response2 = $this->sendCommand('/login', ['name' => $this->user, 'response' => $hexHash]);
            return in_array('!done', $response2);
        }

        return false;
    }

    public function sendCommand(string $command, array $params = []): array
    {
        if (!$this->socket) {
            return [];
        }

        $this->write($command);
        foreach ($params as $key => $value) {
            if (str_starts_with($key, '?')) {
                $this->write($key . '=' . $value);
            } else {
                $this->write('=' . $key . '=' . $value);
            }
        }
        $this->write('');

        return $this->read();
    }

    protected function write(string $word): void
    {
        $len = strlen($word);
        if ($len < 0x80) {
            fwrite($this->socket, chr($len));
        } elseif ($len < 0x4000) {
            fwrite($this->socket, chr(($len >> 8) | 0x80) . chr($len & 0xFF));
        } elseif ($len < 0x200000) {
            fwrite($this->socket, chr(($len >> 16) | 0xC0) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x10000000) {
            fwrite($this->socket, chr(($len >> 24) | 0xE0) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        }
        fwrite($this->socket, $word);
    }

    protected function read(): array
    {
        $response = [];
        $failCount = 0;
        while (true) {
            $word = $this->readWord();
            if ($word === false) {
                $failCount++;
                if ($failCount > 3) break;
                continue;
            }
            if ($word === '') {
                if (!empty($response) && (in_array('!done', $response) || in_array('!trap', $response) || in_array('!fatal', $response))) {
                    break;
                }
                continue;
            }
            $failCount = 0;
            $response[] = $word;
            if ($word === '!fatal') {
                break;
            }
        }
        return $response;
    }

    protected function readWord()
    {
        $byte = @fread($this->socket, 1);
        if ($byte === false || strlen($byte) === 0) {
            return false;
        }
        
        $len = ord($byte);
        if ($len >= 0xF0) {
            return false;
        } elseif ($len >= 0xE0) {
            $len = (($len & 0x0F) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif ($len >= 0xC0) {
            $len = (($len & 0x1F) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif ($len >= 0x80) {
            $len = (($len & 0x3F) << 8) + ord(fread($this->socket, 1));
        }

        if ($len === 0) {
            return '';
        }

        $word = '';
        $remaining = $len;
        while ($remaining > 0) {
            $chunk = @fread($this->socket, $remaining);
            if ($chunk === false || strlen($chunk) === 0) {
                break;
            }
            $word .= $chunk;
            $remaining -= strlen($chunk);
        }
        return $word;
    }

    public function sendPingCommand(string $ip, int $count = 3): array
    {
        if (!$this->socket) {
            return [];
        }

        $oldTimeout = 15;
        stream_set_timeout($this->socket, max(($count * 2) + 5, 15));

        $this->write('/ping');
        $this->write('=address=' . $ip);
        $this->write('=count=' . (string)$count);
        $this->write('');

        $response = [];
        $failCount = 0;
        $maxFails = $count * 5 + 10;
        while (true) {
            $word = $this->readWord();
            if ($word === false) {
                $failCount++;
                if ($failCount > $maxFails) break;
                usleep(100000);
                continue;
            }
            if ($word === '') {
                if (!empty($response) && (in_array('!done', $response) || in_array('!trap', $response) || in_array('!fatal', $response))) {
                    break;
                }
                continue;
            }
            $failCount = 0;
            $response[] = $word;
            if ($word === '!fatal') {
                break;
            }
        }

        stream_set_timeout($this->socket, $oldTimeout);

        return $response;
    }

    public function parseResponse(array $response): array
    {
        $result = [];
        $current = [];

        foreach ($response as $line) {
            if ($line === '!re') {
                if (!empty($current)) {
                    $result[] = $current;
                }
                $current = [];
            } elseif (strpos($line, '=') === 0) {
                $parts = explode('=', substr($line, 1), 2);
                if (count($parts) === 2) {
                    $current[$parts[0]] = $parts[1];
                }
            }
        }

        if (!empty($current)) {
            $result[] = $current;
        }

        return $result;
    }

    protected function isSuccess(array $response): bool
    {
        return in_array('!done', $response) && !in_array('!trap', $response);
    }

    protected function extractError(array $response): string
    {
        foreach ($response as $line) {
            if (strpos($line, '=message=') === 0) {
                return substr($line, 9);
            }
        }
        return 'Unknown error';
    }

    protected function successResponse(string $message, array $data = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message
        ], $data);
    }

    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message
        ];
    }

    protected function logInfo(string $message): void
    {
        Log::channel('daily')->info("[MikroTik] {$message}");
    }

    protected function logError(string $message): void
    {
        Log::channel('daily')->error("[MikroTik] {$message}");
    }

    protected function logMikrotikAction(Client $client, string $action, string $status, string $details = ''): void
    {
        try {
            MikrotikActionLog::create([
                'client_id' => $client->id,
                'mikrotik_device_id' => $this->device?->id,
                'action' => $action,
                'action_type' => 'pppoe',
                'status' => $status,
                'trigger_source' => 'billing_system',
                'request_data' => json_encode(['username' => $client->pppoe_username]),
                'response_data' => $details,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log MikroTik action: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
            $this->connected = false;
        }
    }

    public function syncClientStatusOnRouter($client, string $newStatus, string $oldStatus, ?string $reason = null, ?int $changedBy = null): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            $device = $client->mikrotikDevice ?? \App\Models\MikrotikDevice::where('is_active', true)->first();
            $pppoeUser = $client->username;

            if (!$device || !$pppoeUser) {
                $result['message'] = 'No MikroTik device or username configured.';
                if ($newStatus === 'inactive') {
                    $currentProfile = $client->mikrotik_profile_name
                        ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                    if ($currentProfile && strtolower($currentProfile) !== 'inactive') {
                        $client->update([
                            'previous_mikrotik_profile' => $currentProfile,
                            'mikrotik_profile_name' => 'Inactive',
                        ]);
                    }
                } elseif ($newStatus === 'active') {
                    $restoreProfile = $client->previous_mikrotik_profile
                        ?: $client->mikrotik_profile_name
                        ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                    if ($restoreProfile && strtolower($restoreProfile) !== 'inactive') {
                        $client->update([
                            'mikrotik_profile_name' => $restoreProfile,
                            'previous_mikrotik_profile' => null,
                        ]);
                    }
                }
                return $result;
            }

            $currentProfile = $client->mikrotik_profile_name
                ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);

            if ($newStatus === 'inactive' && $currentProfile && strtolower($currentProfile) !== 'inactive') {
                $client->update([
                    'previous_mikrotik_profile' => $currentProfile,
                    'mikrotik_profile_name' => 'Inactive',
                ]);
            } elseif ($newStatus === 'active') {
                $restoreProfile = $client->previous_mikrotik_profile
                    ?: $client->mikrotik_profile_name
                    ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                if ($restoreProfile && strtolower($restoreProfile) === 'inactive') {
                    $restoreProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
                }
                if ($restoreProfile) {
                    $client->update([
                        'mikrotik_profile_name' => $restoreProfile,
                        'previous_mikrotik_profile' => null,
                    ]);
                }
            }

            $connected = $this->connect(
                $device->getRawOriginal('host') ?? $device->host,
                $device->username,
                $device->password,
                (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
            );

            if (!$connected) {
                $result['message'] = 'Could not connect to MikroTik router (local status updated).';
                return $result;
            }

            $response = $this->sendCommand('/ppp/secret/print', ['?name' => $pppoeUser]);
            $secrets = $this->parseResponse($response);

            if (!empty($secrets)) {
                $secretId = $secrets[0]['.id'] ?? null;
                if ($secretId) {
                    if ($newStatus === 'active') {
                        $profileToSet = $client->mikrotik_profile_name
                            ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);

                        $setParams = ['.id' => $secretId, 'disabled' => 'no'];
                        if ($profileToSet) {
                            $setParams['profile'] = $profileToSet;
                        }
                        $this->sendCommand('/ppp/secret/set', $setParams);
                        $result['message'] = 'MikroTik user enabled' . ($profileToSet ? " and profile restored to '{$profileToSet}'." : '.');
                    } elseif ($newStatus === 'inactive') {
                        $this->sendCommand('/ppp/secret/set', [
                            '.id' => $secretId,
                            'profile' => 'Inactive',
                        ]);

                        $activeResponse = $this->sendCommand('/ppp/active/print', ['?name' => $pppoeUser]);
                        $activeSessions = $this->parseResponse($activeResponse);
                        foreach ($activeSessions as $session) {
                            if (isset($session['.id'])) {
                                $this->sendCommand('/ppp/active/remove', ['.id' => $session['.id']]);
                            }
                        }
                        $result['message'] = 'MikroTik profile shifted to Inactive and session terminated.';
                    }
                    $result['success'] = true;
                }
            }

            $this->disconnect();

            \App\Models\MikrotikActionLog::create([
                'client_id' => $client->id,
                'device_id' => $device->id ?? null,
                'action' => $newStatus === 'active' ? 'enable' : 'disable',
                'action_type' => $newStatus === 'active' ? 'profile_restore' : 'profile_shift',
                'trigger_source' => 'manual_status_change',
                'status' => 'success',
                'details' => json_encode([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                    'changed_by' => $changedBy,
                ]),
            ]);
        } catch (\Throwable $e) {
            $result['message'] = 'MikroTik status sync failed: ' . $e->getMessage();
            \Illuminate\Support\Facades\Log::error("MikroTik status sync failed for client {$client->id}: " . $e->getMessage());
        }

        return $result;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
