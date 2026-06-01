<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MikroTikSimulator extends Command
{
    protected $signature = 'mikrotik:simulator {--port=8728 : Port to listen on}';
    protected $description = 'Run a MikroTik RouterOS API simulator for testing';

    protected $socket;
    protected $clients = [];
    protected $pppoeSecrets = [];
    protected $pppoeActive = [];
    protected $running = true;

    public function handle()
    {
        $port = (int) $this->option('port');
        
        $this->initializeTestData();
        
        $this->info("========================================");
        $this->info("  MikroTik RouterOS API Simulator");
        $this->info("========================================");
        $this->info("Listening on 0.0.0.0:{$port}");
        $this->info("Press Ctrl+C to stop");
        $this->info("");
        $this->info("Test credentials: admin / admin");
        $this->info("========================================");
        
        $this->socket = @stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
        
        if (!$this->socket) {
            $this->error("Failed to create server: {$errstr} ({$errno})");
            return 1;
        }

        stream_set_blocking($this->socket, false);

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function() {
                $this->running = false;
                $this->info("\nShutting down simulator...");
            });
            pcntl_signal(SIGTERM, function() {
                $this->running = false;
            });
        }

        while ($this->running) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            $read = [$this->socket];
            $write = null;
            $except = null;
            
            if (@stream_select($read, $write, $except, 1) > 0) {
                $client = @stream_socket_accept($this->socket, 0);
                if ($client) {
                    $this->handleClient($client);
                }
            }
        }

        fclose($this->socket);
        $this->info("Simulator stopped.");
        
        return 0;
    }

    protected function initializeTestData()
    {
        $clients = \App\Models\Client::whereNotNull('username')->get();
        
        foreach ($clients as $client) {
            $username = $client->pppoe_username ?? $client->username;
            $this->pppoeSecrets[$username] = [
                '.id' => '*' . dechex(crc32($username)),
                'name' => $username,
                'password' => 'secret123',
                'profile' => 'default',
                'service' => 'pppoe',
                'disabled' => $client->status === 'inactive' ? 'true' : 'false',
                'comment' => "Client: {$client->name}",
            ];
            
            if ($client->status === 'active' && $client->connection_status === 'online') {
                $this->pppoeActive[$username] = [
                    '.id' => '*' . dechex(crc32($username . 'active')),
                    'name' => $username,
                    'service' => 'pppoe',
                    'caller-id' => $this->randomMac(),
                    'address' => '10.10.' . rand(1, 254) . '.' . rand(1, 254),
                    'uptime' => rand(1, 86400) . 's',
                    'bytes-in' => rand(1000000, 999999999),
                    'bytes-out' => rand(1000000, 999999999),
                ];
            }
        }
        
        $this->info("Loaded " . count($this->pppoeSecrets) . " PPPoE secrets from database");
        $this->info("Active sessions: " . count($this->pppoeActive));
    }

    protected function randomMac(): string
    {
        return implode(':', array_map(function() {
            return sprintf('%02X', rand(0, 255));
        }, range(1, 6)));
    }

    protected function handleClient($client)
    {
        $peername = stream_socket_get_name($client, true);
        $this->line("[{$peername}] Client connected");
        
        stream_set_timeout($client, 30);
        $authenticated = false;
        
        while (true) {
            $command = $this->readCommand($client);
            
            if ($command === false || $command === null) {
                break;
            }
            
            $this->line("[{$peername}] Command: " . $command['command']);
            
            if (!$authenticated && $command['command'] !== '/login') {
                $this->sendResponse($client, ['!trap', '=message=not logged in']);
                continue;
            }
            
            $response = $this->processCommand($command, $authenticated);
            
            if ($command['command'] === '/login' && in_array('!done', $response)) {
                $authenticated = true;
            }
            
            $this->sendResponse($client, $response);
        }
        
        fclose($client);
        $this->line("[{$peername}] Client disconnected");
    }

    protected function readCommand($client): ?array
    {
        $words = [];
        
        while (true) {
            $word = $this->readWord($client);
            
            if ($word === false) {
                return null;
            }
            
            if ($word === '') {
                break;
            }
            
            $words[] = $word;
        }
        
        if (empty($words)) {
            return null;
        }
        
        $command = $words[0];
        $params = [];
        
        foreach (array_slice($words, 1) as $word) {
            if (strpos($word, '=') === 0) {
                $parts = explode('=', substr($word, 1), 2);
                if (count($parts) === 2) {
                    $params[$parts[0]] = $parts[1];
                }
            }
        }
        
        return ['command' => $command, 'params' => $params];
    }

    protected function readWord($client)
    {
        $byte = @fread($client, 1);
        
        if ($byte === false || strlen($byte) === 0) {
            return false;
        }
        
        $len = ord($byte);
        
        if ($len === 0) {
            return '';
        }
        
        if ($len < 0x80) {
        } elseif ($len < 0xC0) {
            $len = (($len & 0x3F) << 8) + ord(fread($client, 1));
        } elseif ($len < 0xE0) {
            $len = (($len & 0x1F) << 16) + (ord(fread($client, 1)) << 8) + ord(fread($client, 1));
        } elseif ($len < 0xF0) {
            $len = (($len & 0x0F) << 24) + (ord(fread($client, 1)) << 16) + (ord(fread($client, 1)) << 8) + ord(fread($client, 1));
        }
        
        if ($len === 0) {
            return '';
        }
        
        return fread($client, $len);
    }

    protected function writeWord($client, string $word): void
    {
        $len = strlen($word);
        
        if ($len < 0x80) {
            fwrite($client, chr($len));
        } elseif ($len < 0x4000) {
            fwrite($client, chr(($len >> 8) | 0x80) . chr($len & 0xFF));
        } elseif ($len < 0x200000) {
            fwrite($client, chr(($len >> 16) | 0xC0) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x10000000) {
            fwrite($client, chr(($len >> 24) | 0xE0) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        }
        
        if ($len > 0) {
            fwrite($client, $word);
        }
    }

    protected function sendResponse($client, array $words): void
    {
        foreach ($words as $word) {
            $this->writeWord($client, $word);
        }
        $this->writeWord($client, '');
    }

    protected function processCommand(array $command, bool &$authenticated): array
    {
        $cmd = $command['command'];
        $params = $command['params'];
        
        switch ($cmd) {
            case '/login':
                return $this->handleLogin($params);
            
            case '/ppp/secret/print':
                return $this->handlePppSecretPrint($params);
            
            case '/ppp/secret/enable':
                return $this->handlePppSecretEnable($params);
            
            case '/ppp/secret/disable':
                return $this->handlePppSecretDisable($params);
            
            case '/ppp/secret/add':
                return $this->handlePppSecretAdd($params);
            
            case '/ppp/secret/set':
                return $this->handlePppSecretSet($params);
            
            case '/ppp/active/print':
                return $this->handlePppActivePrint($params);
            
            case '/ppp/active/remove':
                return $this->handlePppActiveRemove($params);
            
            case '/system/resource/print':
                return $this->handleSystemResource();
            
            case '/interface/print':
                return $this->handleInterfacePrint();
            
            default:
                $this->warn("Unknown command: {$cmd}");
                return ['!done'];
        }
    }

    protected function handleLogin(array $params): array
    {
        $this->info("  -> Login attempt: " . ($params['name'] ?? 'unknown'));
        return ['!done'];
    }

    protected function handlePppSecretPrint(array $params): array
    {
        $response = [];
        $filter = null;
        
        if (isset($params['?name'])) {
            $filter = $params['?name'];
        }
        
        foreach ($this->pppoeSecrets as $username => $secret) {
            if ($filter !== null && $username !== $filter) {
                continue;
            }
            
            $response[] = '!re';
            foreach ($secret as $key => $value) {
                $response[] = "={$key}={$value}";
            }
        }
        
        $response[] = '!done';
        $this->info("  -> Returned " . (count($response) - 1) / (count($this->pppoeSecrets[array_key_first($this->pppoeSecrets)] ?? []) + 1) . " secrets");
        
        return $response;
    }

    protected function handlePppSecretEnable(array $params): array
    {
        $id = $params['.id'] ?? null;
        
        if (!$id) {
            return ['!trap', '=message=missing .id', '!done'];
        }
        
        foreach ($this->pppoeSecrets as $username => &$secret) {
            if ($secret['.id'] === $id) {
                $secret['disabled'] = 'false';
                $this->info("  -> ENABLED user: {$username}");
                
                $client = \App\Models\Client::where('username', $username)->first();
                if ($client) {
                    $this->pppoeActive[$username] = [
                        '.id' => '*' . dechex(crc32($username . 'active')),
                        'name' => $username,
                        'service' => 'pppoe',
                        'caller-id' => $this->randomMac(),
                        'address' => '10.10.' . rand(1, 254) . '.' . rand(1, 254),
                        'uptime' => '0s',
                        'bytes-in' => 0,
                        'bytes-out' => 0,
                    ];
                }
                
                return ['!done'];
            }
        }
        
        return ['!trap', '=message=no such item', '!done'];
    }

    protected function handlePppSecretDisable(array $params): array
    {
        $id = $params['.id'] ?? null;
        
        if (!$id) {
            return ['!trap', '=message=missing .id', '!done'];
        }
        
        foreach ($this->pppoeSecrets as $username => &$secret) {
            if ($secret['.id'] === $id) {
                $secret['disabled'] = 'true';
                $this->info("  -> DISABLED user: {$username}");
                
                unset($this->pppoeActive[$username]);
                
                return ['!done'];
            }
        }
        
        return ['!trap', '=message=no such item', '!done'];
    }

    protected function handlePppSecretAdd(array $params): array
    {
        $username = $params['name'] ?? null;
        
        if (!$username) {
            return ['!trap', '=message=missing name', '!done'];
        }
        
        if (isset($this->pppoeSecrets[$username])) {
            return ['!trap', '=message=already have such entry', '!done'];
        }
        
        $id = '*' . dechex(crc32($username));
        $this->pppoeSecrets[$username] = [
            '.id' => $id,
            'name' => $username,
            'password' => $params['password'] ?? '',
            'profile' => $params['profile'] ?? 'default',
            'service' => $params['service'] ?? 'pppoe',
            'disabled' => 'false',
            'comment' => $params['comment'] ?? '',
        ];
        
        $this->info("  -> CREATED user: {$username}");
        
        return ['!done', "=ret={$id}"];
    }

    protected function handlePppSecretSet(array $params): array
    {
        $id = $params['.id'] ?? null;
        
        if (!$id) {
            return ['!trap', '=message=missing .id', '!done'];
        }
        
        foreach ($this->pppoeSecrets as $username => &$secret) {
            if ($secret['.id'] === $id) {
                foreach ($params as $key => $value) {
                    if ($key !== '.id') {
                        $secret[$key] = $value;
                    }
                }
                $this->info("  -> UPDATED user: {$username}");
                return ['!done'];
            }
        }
        
        return ['!trap', '=message=no such item', '!done'];
    }

    protected function handlePppActivePrint(array $params): array
    {
        $response = [];
        $filter = null;
        
        if (isset($params['?name'])) {
            $filter = $params['?name'];
        }
        
        foreach ($this->pppoeActive as $username => $active) {
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

    protected function handlePppActiveRemove(array $params): array
    {
        $id = $params['.id'] ?? null;
        
        if (!$id) {
            return ['!trap', '=message=missing .id', '!done'];
        }
        
        foreach ($this->pppoeActive as $username => $active) {
            if ($active['.id'] === $id) {
                unset($this->pppoeActive[$username]);
                $this->info("  -> DISCONNECTED user: {$username}");
                return ['!done'];
            }
        }
        
        return ['!trap', '=message=no such item', '!done'];
    }

    protected function handleSystemResource(): array
    {
        return [
            '!re',
            '=uptime=1d2h3m4s',
            '=version=6.49.6 (stable)',
            '=build-time=Jun/02/2022 08:33:28',
            '=factory-software=6.44.6',
            '=free-memory=128000000',
            '=total-memory=256000000',
            '=cpu=MIPS 74Kc V5.0',
            '=cpu-count=1',
            '=cpu-frequency=400',
            '=cpu-load=5',
            '=free-hdd-space=100000000',
            '=total-hdd-space=128000000',
            '=architecture-name=mipsbe',
            '=board-name=RB750Gr3',
            '=platform=MikroTik',
            '!done'
        ];
    }

    protected function handleInterfacePrint(): array
    {
        return [
            '!re',
            '=.id=*1',
            '=name=ether1',
            '=type=ether',
            '=mtu=1500',
            '=actual-mtu=1500',
            '=running=true',
            '=disabled=false',
            '!re',
            '=.id=*2',
            '=name=ether2',
            '=type=ether',
            '=mtu=1500',
            '=actual-mtu=1500',
            '=running=true',
            '=disabled=false',
            '!re',
            '=.id=*3',
            '=name=pppoe-out1',
            '=type=pppoe-out',
            '=mtu=1480',
            '=actual-mtu=1480',
            '=running=true',
            '=disabled=false',
            '!done'
        ];
    }
}
