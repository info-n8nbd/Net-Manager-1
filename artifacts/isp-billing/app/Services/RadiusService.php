<?php
/**
 * RADIUS Server Integration Service
 * 
 * Provides RADIUS (AAA) integration for ISP authentication, authorization, and accounting
 * 
 * Features:
 * - FreeRADIUS database integration
 * - User creation/management in RADIUS
 * - Accounting data retrieval
 * - Profile/attribute management
 * 
 * RADIUS Architecture:
 * - radcheck: User authentication credentials
 * - radreply: User attributes (IP, bandwidth, etc.)
 * - radacct: Accounting data (sessions, traffic)
 * - radusergroup: Group membership
 * - radgroupcheck/radgroupreply: Group attributes
 */

namespace App\Services;

use App\Models\Client;
use App\Models\Package;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiusService
{
    protected ?string $connection = null;
    protected bool $enabled = false;

    public function __construct()
    {
        $this->enabled = SystemSetting::get('radius_enabled', false);
        $this->connection = SystemSetting::get('radius_db_connection', 'radius');
    }

    /**
     * Check if RADIUS is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get RADIUS database connection
     */
    protected function db()
    {
        return DB::connection($this->connection);
    }

    /**
     * Create RADIUS user for a client
     */
    public function createUser(Client $client, string $password): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        $username = $client->pppoe_username ?? $client->username;
        if (empty($username)) {
            return $this->response(false, 'Username is required');
        }

        try {
            // Check if user exists
            $existing = $this->db()->table('radcheck')
                ->where('username', $username)
                ->first();

            if ($existing) {
                return $this->response(false, 'User already exists in RADIUS');
            }

            // Insert into radcheck (authentication)
            $this->db()->table('radcheck')->insert([
                'username' => $username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $password,
            ]);

            // Add to user group based on package
            $groupName = $client->package?->name ?? 'default';
            $this->db()->table('radusergroup')->insert([
                'username' => $username,
                'groupname' => $groupName,
                'priority' => 1,
            ]);

            // Add reply attributes if needed (static IP, etc.)
            if (!empty($client->ip_address)) {
                $this->db()->table('radreply')->insert([
                    'username' => $username,
                    'attribute' => 'Framed-IP-Address',
                    'op' => ':=',
                    'value' => $client->ip_address,
                ]);
            }

            Log::info("RADIUS user created: {$username}");
            return $this->response(true, 'User created successfully');

        } catch (\Exception $e) {
            Log::error("RADIUS create user error: " . $e->getMessage());
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Update RADIUS user password
     */
    public function updatePassword(string $username, string $newPassword): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        try {
            $updated = $this->db()->table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Cleartext-Password')
                ->update(['value' => $newPassword]);

            if ($updated) {
                Log::info("RADIUS password updated: {$username}");
                return $this->response(true, 'Password updated');
            }

            return $this->response(false, 'User not found');

        } catch (\Exception $e) {
            Log::error("RADIUS update password error: " . $e->getMessage());
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Enable RADIUS user (remove disabled attribute)
     */
    public function enableUser(string $username): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        try {
            $this->db()->table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->where('value', 'Reject')
                ->delete();

            Log::info("RADIUS user enabled: {$username}");
            return $this->response(true, 'User enabled');

        } catch (\Exception $e) {
            Log::error("RADIUS enable user error: " . $e->getMessage());
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Disable RADIUS user (add reject attribute)
     */
    public function disableUser(string $username): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        try {
            // Check if already disabled
            $existing = $this->db()->table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->where('value', 'Reject')
                ->first();

            if (!$existing) {
                $this->db()->table('radcheck')->insert([
                    'username' => $username,
                    'attribute' => 'Auth-Type',
                    'op' => ':=',
                    'value' => 'Reject',
                ]);
            }

            // Disconnect active session
            $this->disconnectUser($username);

            Log::info("RADIUS user disabled: {$username}");
            return $this->response(true, 'User disabled');

        } catch (\Exception $e) {
            Log::error("RADIUS disable user error: " . $e->getMessage());
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Delete RADIUS user
     */
    public function deleteUser(string $username): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        try {
            $this->db()->table('radcheck')->where('username', $username)->delete();
            $this->db()->table('radreply')->where('username', $username)->delete();
            $this->db()->table('radusergroup')->where('username', $username)->delete();

            Log::info("RADIUS user deleted: {$username}");
            return $this->response(true, 'User deleted');

        } catch (\Exception $e) {
            Log::error("RADIUS delete user error: " . $e->getMessage());
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Change user's group (package)
     */
    public function changeUserGroup(string $username, string $newGroupName): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        try {
            $this->db()->table('radusergroup')
                ->where('username', $username)
                ->update(['groupname' => $newGroupName]);

            Log::info("RADIUS user group changed: {$username} -> {$newGroupName}");
            return $this->response(true, 'Group changed');

        } catch (\Exception $e) {
            Log::error("RADIUS change group error: " . $e->getMessage());
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Disconnect active user session
     */
    public function disconnectUser(string $username): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        try {
            // Mark session as terminated in radacct
            $this->db()->table('radacct')
                ->where('username', $username)
                ->whereNull('acctstoptime')
                ->update([
                    'acctstoptime' => now(),
                    'acctterminatecause' => 'Admin-Reset',
                ]);

            return $this->response(true, 'Session terminated');

        } catch (\Exception $e) {
            Log::error("RADIUS disconnect error: " . $e->getMessage());
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Get user's active session
     */
    public function getActiveSession(string $username): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $session = $this->db()->table('radacct')
                ->where('username', $username)
                ->whereNull('acctstoptime')
                ->first();

            if ($session) {
                return [
                    'username' => $session->username,
                    'session_id' => $session->acctsessionid,
                    'start_time' => $session->acctstarttime,
                    'nas_ip' => $session->nasipaddress,
                    'framed_ip' => $session->framedipaddress,
                    'input_octets' => $session->acctinputoctets ?? 0,
                    'output_octets' => $session->acctoutputoctets ?? 0,
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error("RADIUS get session error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user's traffic usage for a period
     */
    public function getUserTraffic(string $username, string $fromDate, string $toDate): array
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            $sessions = $this->db()->table('radacct')
                ->where('username', $username)
                ->whereBetween('acctstarttime', [$fromDate, $toDate])
                ->get();

            $totalInput = 0;
            $totalOutput = 0;
            $totalTime = 0;

            foreach ($sessions as $session) {
                $totalInput += $session->acctinputoctets ?? 0;
                $totalOutput += $session->acctoutputoctets ?? 0;
                $totalTime += $session->acctsessiontime ?? 0;
            }

            return [
                'sessions' => $sessions->count(),
                'input_bytes' => $totalInput,
                'output_bytes' => $totalOutput,
                'total_bytes' => $totalInput + $totalOutput,
                'input_gb' => round($totalInput / 1073741824, 2),
                'output_gb' => round($totalOutput / 1073741824, 2),
                'total_gb' => round(($totalInput + $totalOutput) / 1073741824, 2),
                'total_time_seconds' => $totalTime,
                'total_time_hours' => round($totalTime / 3600, 2),
            ];

        } catch (\Exception $e) {
            Log::error("RADIUS get traffic error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create or update RADIUS group (profile)
     */
    public function createGroup(string $groupName, array $attributes): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        try {
            // Delete existing group attributes
            $this->db()->table('radgroupcheck')->where('groupname', $groupName)->delete();
            $this->db()->table('radgroupreply')->where('groupname', $groupName)->delete();

            // Add new attributes
            foreach ($attributes as $attr => $value) {
                $table = str_contains($attr, 'Password') || str_contains($attr, 'Auth') 
                    ? 'radgroupcheck' 
                    : 'radgroupreply';

                $this->db()->table($table)->insert([
                    'groupname' => $groupName,
                    'attribute' => $attr,
                    'op' => ':=',
                    'value' => $value,
                ]);
            }

            Log::info("RADIUS group created/updated: {$groupName}");
            return $this->response(true, 'Group saved');

        } catch (\Exception $e) {
            Log::error("RADIUS create group error: " . $e->getMessage());
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Sync package to RADIUS group
     */
    public function syncPackageToGroup(Package $package): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        $groupName = $package->name;
        $download = ($package->download_speed ?? 10) * 1000000; // Convert to bps
        $upload = ($package->upload_speed ?? 10) * 1000000;

        $attributes = [
            'Mikrotik-Rate-Limit' => "{$upload}/{$download}",
            'Session-Timeout' => 86400, // 24 hours
            'Idle-Timeout' => 1800, // 30 minutes
        ];

        return $this->createGroup($groupName, $attributes);
    }

    /**
     * Get all RADIUS groups
     */
    public function getGroups(): array
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            $groups = $this->db()->table('radgroupreply')
                ->select('groupname')
                ->distinct()
                ->pluck('groupname')
                ->toArray();

            return $groups;

        } catch (\Exception $e) {
            Log::error("RADIUS get groups error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Test RADIUS database connection
     */
    public function testConnection(): array
    {
        if (!$this->enabled) {
            return $this->response(false, 'RADIUS is not enabled');
        }

        try {
            $this->db()->table('radcheck')->limit(1)->get();
            return $this->response(true, 'Connection successful');

        } catch (\Exception $e) {
            Log::error("RADIUS connection test error: " . $e->getMessage());
            return $this->response(false, 'Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Standard response format
     */
    protected function response(bool $success, string $message, array $data = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];
    }
}
