<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\MikrotikActionLog;
use App\Models\CorrectionAuditLog;
use Illuminate\Support\Facades\Log;
use App\Services\DeviceCircuitBreaker;

class MikroTikService
{
    protected $host;
    protected $user;
    protected $pass;
    protected $port;
    protected $socket;
    protected $debug = false;
    protected $connected = false;
    protected $currentDevice = null;

    public function __construct($host = null, $user = null, $pass = null, $port = 8728)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
    }

    public static function fromDevice(MikrotikDevice $device): self
    {
        $service = new self($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728)));
        $service->currentDevice = $device;
        return $service;
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

        if ($this->connected) {
            return $this;
        }

        try {
            $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
            if (!$this->socket) {
                $devId = $this->currentDevice?->id ?? md5($this->host.$this->port);
                $devName = $this->currentDevice?->name ?? $this->host;
                DeviceCircuitBreaker::onFailure('mikrotik', $devId, $devName, "$errstr ($errno)");
                return false;
            }

            stream_set_timeout($this->socket, 10);
            
            if (!$this->login()) {
                fclose($this->socket);
                return false;
            }

            $this->connected = true;
            return $this;
        } catch (\Exception $e) {
            $devId = $this->currentDevice?->id ?? md5($this->host.$this->port);
            $devName = $this->currentDevice?->name ?? $this->host;
            DeviceCircuitBreaker::onFailure('mikrotik', $devId, $devName, $e->getMessage());
            return false;
        }
    }

    public function comm(string $command, array $params = []): array
    {
        $response = $this->sendCommand($command, $params);
        return $this->parseResponse($response);
    }

    public function suspendClient(Client $client, string $triggerSource, ?int $triggeredBy = null, bool $sendSms = true): array
    {
        $device = $this->getClientDevice($client);
        $actionLog = MikrotikActionLog::log(
            $client, $device, MikrotikActionLog::ACTION_DISABLE,
            MikrotikActionLog::TYPE_PPPOE, $triggerSource, $triggeredBy,
            ['username' => $client->username]
        );

        $result = $this->executeWithRetry($actionLog, function() use ($client) {
            return $this->disableClient($client);
        });

        if ($result['success']) {
            $client->status = 'inactive';
            $client->connection_status = 'offline';
            $client->last_inactive_date = now();
            $client->save();

            CorrectionAuditLog::log('Client', $client->id, 'mikrotik_inactive', null, [
                'trigger' => $triggerSource,
                'username' => $client->username,
                'router_ip' => $device?->ip_address
            ]);

            ClientActivityService::logAutoDisconnect($client, $triggerSource);

            if ($sendSms) {
                app(SmsService::class)->sendDisconnectNotice($client);
            }
        }

        return $result;
    }

    public function restoreClient(Client $client, string $triggerSource, ?int $triggeredBy = null, bool $sendSms = true): array
    {
        $device = $this->getClientDevice($client);
        $actionLog = MikrotikActionLog::log(
            $client, $device, MikrotikActionLog::ACTION_ENABLE,
            MikrotikActionLog::TYPE_PPPOE, $triggerSource, $triggeredBy,
            ['username' => $client->username]
        );

        $result = $this->executeWithRetry($actionLog, function() use ($client) {
            return $this->enableClient($client);
        });

        if ($result['success']) {
            $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
            $restoreProfile = $client->previous_mikrotik_profile ?: $originalProfile;

            $client->status = 'active';
            $client->last_active_date = now();
            if ($restoreProfile) {
                $client->mikrotik_profile_name = $restoreProfile;
                $client->previous_mikrotik_profile = null;
            }
            $client->save();

            CorrectionAuditLog::log('Client', $client->id, 'mikrotik_restore', null, [
                'trigger' => $triggerSource,
                'username' => $client->username,
                'router_ip' => $device?->ip_address
            ]);

            ClientActivityService::logAutoReconnect($client);

            if ($sendSms) {
                app(SmsService::class)->sendServiceRestoration($client);
            }

            try {
                $telegram = new TelegramService();
                $telegram->sendClientConnected($client->client_id ?? $client->id, null, 'Online', $client->name ?? '', $client->username ?? '');
            } catch (\Throwable $e) {
                Log::warning('Telegram connect notification failed: ' . $e->getMessage());
            }
        }

        return $result;
    }

    protected function executeWithRetry(MikrotikActionLog $actionLog, callable $operation): array
    {
        $maxRetries = MikrotikActionLog::MAX_RETRIES;
        $attempt = 0;
        $lastError = null;

        while ($attempt <= $maxRetries) {
            try {
                $device = $actionLog->device ?? $this->getDeviceForClient($actionLog->client);
                
                if ($device && !$this->connected) {
                    $connection = $this->connect($device);
                    if ($connection === false) {
                        throw new \Exception("Failed to connect to MikroTik device: {$device->ip_address}");
                    }
                }

                $success = $operation();
                
                if ($success) {
                    $actionLog->markSuccess(['attempt' => $attempt + 1]);
                    return ['success' => true, 'attempts' => $attempt + 1];
                } else {
                    throw new \Exception("Operation returned false");
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                $attempt++;
                
                if ($attempt <= $maxRetries) {
                    $actionLog->markRetrying();
                    Log::warning("MikroTik operation retry {$attempt}/{$maxRetries}: {$lastError}");
                    usleep(500000);
                    $this->disconnect();
                }
            }
        }

        $actionLog->markFailed($lastError);
        Log::error("MikroTik operation failed after {$maxRetries} retries: {$lastError}");
        
        return [
            'success' => false,
            'error' => $lastError,
            'attempts' => $attempt,
            'action_log_id' => $actionLog->id
        ];
    }

    protected function getClientDevice(Client $client): ?MikrotikDevice
    {
        if ($client->mikrotik_device_id) {
            return MikrotikDevice::find($client->mikrotik_device_id);
        }

        if ($client->zone && $client->zone->mikrotik_device_id) {
            return MikrotikDevice::find($client->zone->mikrotik_device_id);
        }

        return MikrotikDevice::where('is_active', true)->first();
    }

    protected function getDeviceForClient(?Client $client): ?MikrotikDevice
    {
        if (!$client) return null;
        return $this->getClientDevice($client);
    }

    public function retryFailedActions(): array
    {
        $retried = 0;
        $success = 0;
        $stillFailed = 0;

        $failedLogs = MikrotikActionLog::retryable()->orderBy('created_at')->get();

        foreach ($failedLogs as $log) {
            $retried++;
            
            $operation = match ($log->action) {
                MikrotikActionLog::ACTION_ENABLE => fn() => $this->enableClient($log->client),
                MikrotikActionLog::ACTION_DISABLE => fn() => $this->disableClient($log->client),
                default => fn() => false,
            };

            $result = $this->executeWithRetry($log, $operation);
            
            if ($result['success']) {
                $success++;
            } else {
                $stillFailed++;
            }
        }

        return [
            'retried' => $retried,
            'success' => $success,
            'still_failed' => $stillFailed
        ];
    }

    public static function getFailedActionCount(): int
    {
        return MikrotikActionLog::where('status', MikrotikActionLog::STATUS_FAILED)->count();
    }

    public static function getPendingActionCount(): int
    {
        return MikrotikActionLog::where('status', MikrotikActionLog::STATUS_PENDING)->count();
    }

    public function enableClient(Client $client): bool
    {
        if (!$client->username) return false;
        return $this->enablePPPoEUser($client->username);
    }

    public function disableClient(Client $client): bool
    {
        if (!$client->username) return false;
        return $this->disablePPPoEUser($client->username);
    }

    public function disconnect(): void
    {
        if ($this->socket && is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
        $this->connected = false;
    }

    protected function login(): bool
    {
        $response = $this->sendCommand('/login', ['name' => $this->user, 'password' => $this->pass]);

        if (isset($response[0]) && $response[0] === '!done') {
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
            return isset($response2[0]) && $response2[0] === '!done';
        }

        return false;
    }

    public function sendCommand(string $command, array $params = []): array
    {
        if (!$this->socket && !$this->connect()) {
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
        $receivedTerminal = false;
        while (true) {
            $word = $this->readWord();
            if ($word === false) {
                break;
            }
            if ($word === '') {
                if ($receivedTerminal) {
                    break;
                }
                continue;
            }
            $response[] = $word;
            if ($word === '!done' || $word === '!trap' || $word === '!fatal') {
                $receivedTerminal = true;
            }
        }
        return $response;
    }

    protected function readWord()
    {
        $byte = fread($this->socket, 1);
        if ($byte === false || strlen($byte) === 0) {
            return false;
        }
        
        $len = ord($byte);
        if ($len < 0x80) {
        } elseif ($len < 0xC0) {
            $len = (($len & 0x3F) << 8) + ord(fread($this->socket, 1));
        } elseif ($len < 0xE0) {
            $len = (($len & 0x1F) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif ($len < 0xF0) {
            $len = (($len & 0x0F) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
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

    public function getAllPPPoESecrets(): array
    {
        $response = $this->sendCommand('/ppp/secret/print');
        return $this->parseResponse($response);
    }

    public function getPPPoEActiveUsers(): array
    {
        $response = $this->sendCommand('/ppp/active/print');
        return $this->parseResponse($response);
    }

    public function getHotspotActiveUsers(): array
    {
        $response = $this->sendCommand('/ip/hotspot/active/print');
        return $this->parseResponse($response);
    }

    public function isUserOnline(string $username): bool
    {
        $response = $this->sendCommand('/ppp/active/print', ['?name' => $username]);
        $users = $this->parseResponse($response);
        return count($users) > 0;
    }

    public function getUserInfo(string $username): ?array
    {
        $response = $this->sendCommand('/ppp/active/print', ['?name' => $username]);
        $users = $this->parseResponse($response);
        return $users[0] ?? null;
    }

    public function getUserTraffic(string $username): array
    {
        $info = $this->getUserInfo($username);
        if (!$info) {
            return ['download' => 0, 'upload' => 0];
        }

        return [
            'download' => $this->parseBytes($info['bytes-in'] ?? 0),
            'upload' => $this->parseBytes($info['bytes-out'] ?? 0),
            'download_speed' => $this->parseSpeed($info['rate-in'] ?? 0),
            'upload_speed' => $this->parseSpeed($info['rate-out'] ?? 0),
        ];
    }

    public function disconnectUser(string $username): bool
    {
        $response = $this->sendCommand('/ppp/active/print', ['?name' => $username]);
        $users = $this->parseResponse($response);
        
        if (empty($users)) {
            return false;
        }

        $id = $users[0]['.id'] ?? null;
        if ($id) {
            $this->sendCommand('/ppp/active/remove', ['.id' => $id]);
            return true;
        }
        return false;
    }

    public function enablePPPoEUser(string $username): bool
    {
        $response = $this->sendCommand('/ppp/secret/print', ['?name' => $username]);
        $users = $this->parseResponse($response);
        
        if (empty($users)) {
            return false;
        }

        $id = $users[0]['.id'] ?? null;
        if ($id) {
            $this->sendCommand('/ppp/secret/enable', ['.id' => $id]);
            return true;
        }
        return false;
    }

    public function disablePPPoEUser(string $username): bool
    {
        $this->disconnectUser($username);
        
        $response = $this->sendCommand('/ppp/secret/print', ['?name' => $username]);
        $users = $this->parseResponse($response);
        
        if (empty($users)) {
            return false;
        }

        $id = $users[0]['.id'] ?? null;
        if ($id) {
            $this->sendCommand('/ppp/secret/disable', ['.id' => $id]);
            return true;
        }
        return false;
    }

    public function changePPPoEProfile(string $username, string $newProfile): bool
    {
        $response = $this->sendCommand('/ppp/secret/print', ['?name' => $username]);
        $users = $this->parseResponse($response);

        if (empty($users)) {
            return false;
        }

        $id = $users[0]['.id'] ?? null;
        if (!$id) {
            return false;
        }

        $this->sendCommand('/ppp/secret/set', ['.id' => $id, 'profile' => $newProfile]);

        $this->disconnectActiveSession($username);

        return true;
    }

    public function disconnectActiveSession(string $username): bool
    {
        $response = $this->sendCommand('/ppp/active/print', ['?name' => $username]);
        $sessions = $this->parseResponse($response);

        if (empty($sessions)) {
            return false;
        }

        foreach ($sessions as $session) {
            $sessionId = $session['.id'] ?? null;
            if ($sessionId) {
                $this->sendCommand('/ppp/active/remove', ['.id' => $sessionId]);
            }
        }

        return true;
    }

    public function getPPPoEUserProfile(string $username): ?string
    {
        $response = $this->sendCommand('/ppp/secret/print', ['?name' => $username]);
        $users = $this->parseResponse($response);

        if (empty($users)) {
            return null;
        }

        return $users[0]['profile'] ?? null;
    }

    public function createPPPoEUser(string $username, string $password, string $profile, string $service = 'pppoe'): bool
    {
        $response = $this->sendCommand('/ppp/secret/add', [
            'name' => $username,
            'password' => $password,
            'profile' => $profile,
            'service' => $service,
        ]);
        return isset($response[0]) && strpos($response[0], '!done') !== false;
    }

    public function updatePPPoEUser(string $username, array $data): bool
    {
        $response = $this->sendCommand('/ppp/secret/print', ['?name' => $username]);
        $users = $this->parseResponse($response);
        
        if (empty($users)) {
            return false;
        }

        $id = $users[0]['.id'] ?? null;
        if (!$id) {
            return false;
        }

        $params = ['.id' => $id];
        foreach ($data as $key => $value) {
            $params[$key] = $value;
        }

        $response = $this->sendCommand('/ppp/secret/set', $params);
        return true;
    }

    public function removePPPoEUser(string $username): bool
    {
        $response = $this->sendCommand('/ppp/secret/print', ['?name' => $username]);
        $users = $this->parseResponse($response);

        if (empty($users)) {
            return false;
        }

        $id = $users[0]['.id'] ?? null;
        if (!$id) {
            return false;
        }

        try {
            $this->disconnectActiveSession($username);
        } catch (\Throwable $e) {}

        $this->sendCommand('/ppp/secret/remove', ['.id' => $id]);
        return true;
    }

    public function getSystemResources(): array
    {
        $response = $this->sendCommand('/system/resource/print');
        return $this->parseResponse($response)[0] ?? [];
    }

    public function getSystemIdentity(): ?string
    {
        try {
            $response = $this->sendCommand('/system/identity/print');
            $parsed = $this->parseResponse($response);
            return $parsed[0]['name'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getActiveSessions(): array
    {
        $response = $this->sendCommand('/ppp/active/print');
        return $this->parseResponse($response);
    }

    public function getInterfaces(): array
    {
        $response = $this->sendCommand('/interface/print');
        return $this->parseResponse($response);
    }

    protected function parseResponse(array $response): array
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

    protected function parseBytes($bytes): float
    {
        return round($bytes / 1024 / 1024, 2);
    }

    protected function parseSpeed($speed): float
    {
        return round($speed / 1024, 2);
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
