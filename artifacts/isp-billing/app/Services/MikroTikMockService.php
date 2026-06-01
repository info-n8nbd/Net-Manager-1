<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\MikrotikActionLog;
use App\Models\CorrectionAuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MikroTikMockService extends MikroTikService
{
    protected static $mockUsers = [];
    protected static $activeUsers = [];
    protected static $initialized = false;

    public function __construct($host = null, $user = null, $pass = null, $port = 8728)
    {
        parent::__construct($host, $user, $pass, $port);
        $this->initializeMockData();
    }

    protected function initializeMockData(): void
    {
        self::$mockUsers = [];
        self::$activeUsers = [];

        $clients = Client::whereNotNull('username')
            ->where('status', '!=', 'deleted')
            ->with('package')
            ->get();
        
        foreach ($clients as $client) {
            $username = $client->username;
            $profileName = $client->package ? $client->package->name : 'default';
            self::$mockUsers[$username] = [
                '.id' => '*' . dechex(crc32($username)),
                'name' => $username,
                'password' => $client->pppoe_password ?? 'secret123',
                'profile' => $profileName,
                'service' => $client->connection_type ?? 'pppoe',
                'disabled' => $client->status === 'inactive' ? 'true' : 'false',
                'comment' => "Client: {$client->client_id} - {$client->name}",
                'last-logged-out' => $client->last_logout ?? '',
            ];
            
            if ($client->status === 'active') {
                self::$activeUsers[$username] = [
                    '.id' => '*' . dechex(crc32($username . 'active')),
                    'name' => $username,
                    'address' => $client->ip_address ?? ('10.10.' . rand(1, 254) . '.' . rand(1, 254)),
                    'uptime' => rand(1, 86400) . 's',
                ];
            }
        }
        
        self::$initialized = true;
        Log::info('MikroTikMockService initialized with ' . count(self::$mockUsers) . ' users from billing');
    }

    public function connect(?MikrotikDevice $device = null): bool|self
    {
        if ($device) {
            $this->host = $device->getRawOriginal('host') ?? $device->host;
            $this->user = $device->username;
            $this->pass = $device->password;
            $this->port = (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            $this->currentDevice = $device;
        }

        $this->connected = true;
        Log::info("MikroTikMockService: Connected to simulated router at {$this->host}:{$this->port}");
        
        return $this;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        Log::info("MikroTikMockService: Disconnected from simulated router");
    }

    public function sendCommand(string $command, array $params = []): array
    {
        Log::info("MikroTikMockService: Command {$command}", $params);
        
        switch ($command) {
            case '/login':
                return ['!done'];
            
            case '/ppp/secret/print':
                return $this->mockPppSecretPrint($params);
            
            case '/ppp/secret/enable':
                return $this->mockPppSecretEnable($params);
            
            case '/ppp/secret/disable':
                return $this->mockPppSecretDisable($params);
            
            case '/ppp/secret/add':
                return $this->mockPppSecretAdd($params);
            
            case '/ppp/secret/set':
                return $this->mockPppSecretSet($params);
            
            case '/ppp/secret/remove':
                return $this->mockPppSecretRemove($params);
            
            case '/ppp/active/print':
                return $this->mockPppActivePrint($params);
            
            case '/ppp/active/remove':
                return $this->mockPppActiveRemove($params);
            
            case '/system/resource/print':
                return $this->mockSystemResource();
            
            default:
                return ['!done'];
        }
    }

    protected function mockPppSecretPrint(array $params): array
    {
        $response = [];
        $filter = $params['?name'] ?? null;
        
        foreach (self::$mockUsers as $username => $user) {
            if ($filter !== null && $username !== $filter) {
                continue;
            }
            
            $response[] = '!re';
            foreach ($user as $key => $value) {
                $response[] = "={$key}={$value}";
            }
        }
        
        $response[] = '!done';
        return $response;
    }

    protected function mockPppSecretEnable(array $params): array
    {
        $id = $params['.id'] ?? null;
        
        foreach (self::$mockUsers as $username => &$user) {
            if ($user['.id'] === $id) {
                $user['disabled'] = 'false';
                
                self::$activeUsers[$username] = [
                    '.id' => '*' . dechex(crc32($username . 'active')),
                    'name' => $username,
                    'address' => '10.10.' . rand(1, 254) . '.' . rand(1, 254),
                    'uptime' => '0s',
                ];
                
                Log::info("MikroTikMockService: ENABLED user {$username}");
                return ['!done'];
            }
        }
        
        return ['!trap', '=message=no such item', '!done'];
    }

    protected function mockPppSecretDisable(array $params): array
    {
        $id = $params['.id'] ?? null;
        
        foreach (self::$mockUsers as $username => &$user) {
            if ($user['.id'] === $id) {
                $user['disabled'] = 'true';
                unset(self::$activeUsers[$username]);
                
                Log::info("MikroTikMockService: DISABLED user {$username}");
                return ['!done'];
            }
        }
        
        return ['!trap', '=message=no such item', '!done'];
    }

    protected function mockPppSecretAdd(array $params): array
    {
        $username = $params['name'] ?? null;
        
        if (!$username) {
            return ['!trap', '=message=missing name', '!done'];
        }
        
        if (isset(self::$mockUsers[$username])) {
            return ['!trap', '=message=already have such entry', '!done'];
        }
        
        $id = '*' . dechex(crc32($username));
        self::$mockUsers[$username] = [
            '.id' => $id,
            'name' => $username,
            'password' => $params['password'] ?? '',
            'profile' => $params['profile'] ?? 'default',
            'service' => $params['service'] ?? 'pppoe',
            'disabled' => 'false',
            'comment' => $params['comment'] ?? '',
        ];
        
        Log::info("MikroTikMockService: CREATED user {$username}");
        return ['!done', "=ret={$id}"];
    }

    protected function mockPppSecretSet(array $params): array
    {
        $id = $params['.id'] ?? null;

        foreach (self::$mockUsers as $username => &$user) {
            if ($user['.id'] === $id || $username === $id) {
                if (isset($params['profile'])) {
                    $user['profile'] = $params['profile'];
                }
                if (isset($params['password'])) {
                    $user['password'] = $params['password'];
                }
                if (isset($params['comment'])) {
                    $user['comment'] = $params['comment'];
                }
                if (isset($params['disabled'])) {
                    $user['disabled'] = $params['disabled'];
                }
                Log::info("MikroTikMockService: UPDATED user {$username}", $params);
                return ['!done'];
            }
        }
        unset($user);

        return ['!trap', '=message=no such item', '!done'];
    }

    protected function mockPppSecretRemove(array $params): array
    {
        $id = $params['.id'] ?? null;

        foreach (self::$mockUsers as $username => $user) {
            if ($user['.id'] === $id || $username === $id) {
                unset(self::$mockUsers[$username]);
                unset(self::$activeUsers[$username]);
                Log::info("MikroTikMockService: REMOVED user {$username}");
                return ['!done'];
            }
        }

        return ['!trap', '=message=no such item', '!done'];
    }

    protected function mockPppActivePrint(array $params): array
    {
        $response = [];
        $filter = $params['?name'] ?? null;
        
        foreach (self::$activeUsers as $username => $active) {
            if ($filter !== null && $username !== $filter) {
                continue;
            }
            
            $response[] = '!re';
            foreach ($active as $key => $value) {
                $response[] = "={$key}={$value}";
            }
        }
        
        $response[] = '!done';
        return $response;
    }

    protected function mockPppActiveRemove(array $params): array
    {
        $id = $params['.id'] ?? null;
        
        foreach (self::$activeUsers as $username => $active) {
            if ($active['.id'] === $id) {
                unset(self::$activeUsers[$username]);
                Log::info("MikroTikMockService: DISCONNECTED user {$username}");
                return ['!done'];
            }
        }
        
        return ['!trap', '=message=no such item', '!done'];
    }

    protected function mockSystemResource(): array
    {
        return [
            '!re',
            '=uptime=1d2h3m4s',
            '=version=6.49.6 (stable)',
            '=free-memory=128000000',
            '=total-memory=256000000',
            '=cpu-load=5',
            '=board-name=MikroTik-Simulator',
            '!done'
        ];
    }

    public function getSystemResources(): array
    {
        return [
            'uptime' => '1d2h3m4s',
            'version' => '6.49.6 (stable)',
            'free-memory' => '128000000',
            'total-memory' => '256000000',
            'cpu-load' => '5',
            'board-name' => 'MikroTik-Simulator',
            'platform' => 'MikroTik',
            'architecture-name' => 'x86_64',
        ];
    }

    public function getSystemIdentity(): ?string
    {
        $deviceName = $this->currentDevice->name ?? 'Simulator';
        return $deviceName;
    }

    public function getActiveSessions(): array
    {
        $sessions = [];
        foreach (self::$activeUsers as $username => $data) {
            $sessions[] = [
                '.id' => $data['.id'] ?? '*' . dechex(crc32($username)),
                'name' => $username,
                'address' => $data['address'] ?? '10.10.' . rand(1, 254) . '.' . rand(1, 254),
                'uptime' => $data['uptime'] ?? rand(60, 86400) . 's',
                'caller-id' => $data['caller-id'] ?? '',
                'service' => 'pppoe',
            ];
        }
        return $sessions;
    }

    public function getInterfaces(): array
    {
        return [
            ['name' => 'ether1', 'type' => 'ether', 'running' => 'true', 'disabled' => 'false', 'mtu' => '1500'],
            ['name' => 'ether2', 'type' => 'ether', 'running' => 'true', 'disabled' => 'false', 'mtu' => '1500'],
            ['name' => 'bridge1', 'type' => 'bridge', 'running' => 'true', 'disabled' => 'false', 'mtu' => '1500'],
            ['name' => 'pppoe-out1', 'type' => 'pppoe-client', 'running' => 'true', 'disabled' => 'false', 'mtu' => '1480'],
        ];
    }

    public function getAllPPPoESecrets(): array
    {
        return array_values(self::$mockUsers);
    }

    public static function isSimulatorMode(): bool
    {
        return config('mikrotik.use_simulator', false) || 
               env('MIKROTIK_SIMULATOR', false) ||
               MikrotikDevice::where('name', 'Simulator')->where('is_active', true)->exists();
    }

    public static function getInstance(?MikrotikDevice $device = null): MikroTikService
    {
        if (self::isSimulatorMode()) {
            $mock = new self();
            if ($device) {
                $mock->connect($device);
            }
            return $mock;
        }
        
        return $device ? MikroTikService::fromDevice($device) : new MikroTikService();
    }

    public static function getStatus(): array
    {
        $mock = new self();
        return [
            'mode' => 'simulator',
            'total_users' => count(self::$mockUsers),
            'active_users' => count(self::$activeUsers),
            'enabled_users' => count(array_filter(self::$mockUsers, fn($u) => $u['disabled'] === 'false')),
            'disabled_users' => count(array_filter(self::$mockUsers, fn($u) => $u['disabled'] === 'true')),
        ];
    }
}
