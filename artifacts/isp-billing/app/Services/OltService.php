<?php

namespace App\Services;

use App\Models\OltDevice;
use Illuminate\Support\Facades\Log;

class OltService
{
    protected $host;
    protected $user;
    protected $pass;
    protected $port;
    protected $vendor;
    protected $connectionType;
    protected $connection;
    protected $sshConnection;
    protected $sshStream;
    protected $connected = false;
    protected $device;
    protected $lastError = '';
    protected $connectionMethod = '';
    protected $cookieFile = null;

    const VENDOR_HUAWEI = 'huawei';
    const VENDOR_ZTE = 'zte';
    const VENDOR_BDCOM = 'bdcom';
    const VENDOR_VSOL = 'vsol';
    const VENDOR_FIBERHOME = 'fiberhome';
    const VENDOR_CDATA = 'cdata';
    const VENDOR_NETLINK = 'netlink';

    const CONN_TELNET = 'telnet';
    const CONN_SSH = 'ssh';
    const CONN_HTTP = 'http';
    const CONN_SNMP = 'snmp';

    /**
     * BDCOM / EPON ONU statuses that mean the ONU is actively online.
     * "auto-configured" means ONU re-registered after deregistration — it IS online.
     * "configured", "onu-configured", "discovered", "registering" — transition states, treat as online.
     */
    const ONLINE_STATUSES = [
        'online', 'auto-configured', 'configured', 'onu-configured',
        'auto-register', 'autoregister', 'discovered', 'registering',
        'registered', 'working', 'active',
    ];

    public static function isOnlineStatus(string $status): bool
    {
        return in_array(strtolower(trim($status)), self::ONLINE_STATUSES, true);
    }

    /** Map raw BDCOM status to canonical 'online' | 'offline', and return the down_reason if offline */
    public static function normalizeOnuStatus(string $rawStatus): array
    {
        $raw = strtolower(trim($rawStatus));
        if (self::isOnlineStatus($raw)) {
            return ['status' => 'online', 'down_reason' => null];
        }
        // Map known offline reasons to cleaner names
        $downReason = match (true) {
            str_contains($raw, 'dying')        => 'power-off',
            str_contains($raw, 'power')        => 'power-off',
            str_contains($raw, 'mpcp')         => 'wire-down',
            str_contains($raw, 'oam')          => 'wire-down',
            str_contains($raw, 'wire')         => 'wire-down',
            str_contains($raw, 'link')         => 'wire-down',
            str_contains($raw, 'los')          => 'los',
            str_contains($raw, 'deregister')   => 'offline',
            str_contains($raw, 'offline')      => 'offline',
            default                            => $raw ?: 'offline',
        };
        return ['status' => 'offline', 'down_reason' => $downReason];
    }

    public function __construct($host = null, $user = null, $pass = null, $port = 23, $vendor = 'huawei', $connectionType = 'telnet')
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->vendor = strtolower($vendor);
        $this->connectionType = strtolower($connectionType);
    }

    public static function fromDevice(OltDevice $device): self
    {
        $connType = $device->connection_type ?? 'telnet';

        $port = match ($connType) {
            'ssh' => $device->ssh_port ?? $device->port ?? 22,
            'http' => $device->http_port ?? $device->port ?? 80,
            'snmp' => $device->snmp_port ?? 161,
            default => $device->telnet_port ?? $device->port ?? 23,
        };

        $service = new self(
            $device->ip_address,
            $device->username,
            $device->password,
            $port,
            $device->vendor,
            $connType
        );
        $service->device = $device;
        return $service;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getConnectionMethod(): string
    {
        return $this->connectionMethod;
    }

    public function connect(): bool
    {
        if ($this->connected) {
            return true;
        }

        $this->lastError = '';

        return match ($this->connectionType) {
            self::CONN_SSH => $this->connectSsh(),
            self::CONN_HTTP => $this->connectHttp(),
            self::CONN_SNMP => $this->connectSnmp(),
            default => $this->connectTelnet(),
        };
    }

    protected function connectTelnet(): bool
    {
        try {
            $this->connection = @fsockopen($this->host, $this->port, $errno, $errstr, 10);
            if (!$this->connection) {
                $this->lastError = "Telnet connection failed to {$this->host}:{$this->port} - {$errstr} ({$errno})";
                Log::error("OLT: " . $this->lastError);
                return false;
            }

            stream_set_timeout($this->connection, 30);

            if (!$this->loginTelnet()) {
                if (is_resource($this->connection)) {
                    @fclose($this->connection);
                }
                $this->lastError = $this->lastError ?: "Telnet login failed for {$this->user}@{$this->host}:{$this->port}";
                Log::error("OLT: " . $this->lastError);
                return false;
            }

            $this->connected = true;
            $this->connectionMethod = 'Telnet';
            Log::info("OLT connected via Telnet: {$this->host}:{$this->port}");
            return true;
        } catch (\Exception $e) {
            $this->lastError = "Telnet error: " . $e->getMessage();
            Log::error("OLT: " . $this->lastError);
            return false;
        }
    }

    protected function connectSsh(): bool
    {
        try {
            if (!function_exists('ssh2_connect')) {
                $this->lastError = "SSH2 extension not installed. Install php-ssh2 or use Telnet/HTTP.";
                Log::error("OLT: " . $this->lastError);
                return $this->connectSshProc();
            }

            $this->sshConnection = @ssh2_connect($this->host, $this->port);
            if (!$this->sshConnection) {
                $this->lastError = "SSH connection failed to {$this->host}:{$this->port}";
                Log::error("OLT: " . $this->lastError);
                return false;
            }

            if (!@ssh2_auth_password($this->sshConnection, $this->user, $this->pass)) {
                $this->lastError = "SSH authentication failed for {$this->user}@{$this->host}:{$this->port}";
                Log::error("OLT: " . $this->lastError);
                return false;
            }

            $this->sshStream = ssh2_shell($this->sshConnection, 'xterm', null, 200, 50, SSH2_TERM_UNIT_CHARS);
            if (!$this->sshStream) {
                $this->lastError = "SSH shell failed on {$this->host}:{$this->port}";
                Log::error("OLT: " . $this->lastError);
                return false;
            }

            stream_set_blocking($this->sshStream, true);
            stream_set_timeout($this->sshStream, 30);
            $this->connection = $this->sshStream;

            usleep(500000);
            @fread($this->connection, 4096);

            $this->connected = true;
            $this->connectionMethod = 'SSH (ssh2)';
            Log::info("OLT connected via SSH: {$this->host}:{$this->port}");
            return true;
        } catch (\Exception $e) {
            $this->lastError = "SSH error: " . $e->getMessage();
            Log::error("OLT: " . $this->lastError);
            return false;
        }
    }

    protected function connectSshProc(): bool
    {
        try {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $cmd = sprintf(
                'sshpass -p %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d %s@%s',
                escapeshellarg($this->pass),
                $this->port,
                escapeshellarg($this->user),
                escapeshellarg($this->host)
            );

            $process = @proc_open($cmd, $descriptors, $pipes);

            if (!is_resource($process)) {
                $this->lastError = "SSH process failed. Install sshpass or use Telnet/HTTP.";
                Log::error("OLT: " . $this->lastError);
                return false;
            }

            stream_set_blocking($pipes[1], false);
            stream_set_timeout($pipes[1], 10);

            usleep(2000000);
            $output = @fread($pipes[1], 4096);

            if (stripos($output, 'Permission denied') !== false || stripos($output, 'password') !== false) {
                $this->lastError = "SSH authentication failed for {$this->user}@{$this->host}:{$this->port}";
                proc_terminate($process);
                return false;
            }

            $this->connection = $pipes[1];
            $this->sshConnection = $process;
            $this->sshStream = $pipes[0];
            $this->connected = true;
            $this->connectionMethod = 'SSH (proc)';
            Log::info("OLT connected via SSH (proc): {$this->host}:{$this->port}");
            return true;
        } catch (\Exception $e) {
            $this->lastError = "SSH proc error: " . $e->getMessage();
            Log::error("OLT: " . $this->lastError);
            return false;
        }
    }

    protected function connectHttp(): bool
    {
        try {
            $this->cookieFile = tempnam(sys_get_temp_dir(), 'olt_cookie_');

            $urls = [
                "http://{$this->host}:{$this->port}/index.asp",
                "http://{$this->host}:{$this->port}/",
                "http://{$this->host}:{$this->port}/login.asp",
                "http://{$this->host}:{$this->port}/cgi-bin/login.cgi",
            ];

            foreach ($urls as $url) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_USERPWD => "{$this->user}:{$this->pass}",
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC | CURLAUTH_DIGEST,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_COOKIEJAR => $this->cookieFile,
                    CURLOPT_COOKIEFILE => $this->cookieFile,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($httpCode === 200 && !empty($response)) {
                    $this->connected = true;
                    $this->connectionMethod = 'HTTP';
                    Log::info("OLT connected via HTTP: {$url}");
                    return true;
                }

                if ($httpCode === 401) {
                    $this->lastError = "HTTP auth failed (401) at {$url} - check username/password";
                    Log::error("OLT: " . $this->lastError);
                    return false;
                }
            }

            $this->lastError = $curlError ?: "HTTP connection failed to {$this->host}:{$this->port} (all URLs tried)";
            Log::error("OLT: " . $this->lastError);
            return false;
        } catch (\Exception $e) {
            $this->lastError = "HTTP error: " . $e->getMessage();
            Log::error("OLT: " . $this->lastError);
            return false;
        }
    }

    public function httpGet(string $path): ?string
    {
        $url = "http://{$this->host}:{$this->port}/{$path}";
        $ch = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => "{$this->user}:{$this->pass}",
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC | CURLAUTH_DIGEST,
        ];
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            $opts[CURLOPT_COOKIEJAR] = $this->cookieFile;
            $opts[CURLOPT_COOKIEFILE] = $this->cookieFile;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 401 && $this->cookieFile) {
            Log::info("OLT httpGet: 401 on {$path}, re-authenticating...");
            if ($this->connectHttp()) {
                return $this->httpGetWithCookie($path);
            }
        }

        if ($httpCode !== 200) {
            Log::error("OLT httpGet failed: {$path} => HTTP {$httpCode}" . ($error ? " ({$error})" : ''));
            return null;
        }

        return $response;
    }

    protected function httpGetWithCookie(string $path): ?string
    {
        $url = "http://{$this->host}:{$this->port}/{$path}";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => "{$this->user}:{$this->pass}",
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC | CURLAUTH_DIGEST,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200) ? $response : null;
    }

    public function bdcomHttpGet(string $path): ?string
    {
        return $this->httpGet($path);
    }

    protected function connectSnmp(): bool
    {
        try {
            if (!function_exists('snmp2_get')) {
                $this->lastError = "SNMP extension not installed. Install php-snmp.";
                Log::error("OLT: " . $this->lastError);
                return $this->connectSnmpCli();
            }

            $community = $this->device->snmp_community ?? $this->user ?: 'public';
            $snmpPort = $this->device->snmp_port ?? $this->port;

            @snmp_set_quick_print(1);
            @snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

            $sysDescr = @snmp2_get("{$this->host}:{$snmpPort}", $community, '.1.3.6.1.2.1.1.1.0', 5000000, 1);

            if ($sysDescr !== false) {
                $this->connected = true;
                $this->connectionMethod = 'SNMP';
                Log::info("OLT connected via SNMP: {$this->host}:{$snmpPort} - {$sysDescr}");
                return true;
            }

            $this->lastError = "SNMP query failed to {$this->host}:{$snmpPort} with community '{$community}'";
            Log::error("OLT: " . $this->lastError);
            return false;
        } catch (\Exception $e) {
            $this->lastError = "SNMP error: " . $e->getMessage();
            Log::error("OLT: " . $this->lastError);
            return false;
        }
    }

    protected function connectSnmpCli(): bool
    {
        try {
            $community = $this->device->snmp_community ?? $this->user ?: 'public';
            $snmpPort = $this->device->snmp_port ?? $this->port;

            $cmd = sprintf(
                'snmpget -v2c -c %s -t 5 -r 1 %s:%d .1.3.6.1.2.1.1.1.0 2>&1',
                escapeshellarg($community),
                escapeshellarg($this->host),
                $snmpPort
            );

            $output = @shell_exec($cmd);

            if ($output && !str_contains($output, 'Timeout') && !str_contains($output, 'Error')) {
                $this->connected = true;
                $this->connectionMethod = 'SNMP (CLI)';
                Log::info("OLT connected via SNMP CLI: {$this->host}:{$snmpPort}");
                return true;
            }

            $this->lastError = "SNMP CLI failed: " . trim($output ?? 'snmpget not available');
            Log::error("OLT: " . $this->lastError);
            return false;
        } catch (\Exception $e) {
            $this->lastError = "SNMP CLI error: " . $e->getMessage();
            Log::error("OLT: " . $this->lastError);
            return false;
        }
    }

    public function snmpGet(string $oid): ?string
    {
        $community = $this->device->snmp_community ?? $this->user ?: 'public';
        $snmpPort = $this->device->snmp_port ?? $this->port;

        if (function_exists('snmp2_get')) {
            $result = @snmp2_get("{$this->host}:{$snmpPort}", $community, $oid, 5000000, 1);
            return ($result !== false) ? $result : null;
        }

        $cmd = sprintf(
            'snmpget -v2c -c %s -t 5 -r 1 %s:%d %s 2>&1',
            escapeshellarg($community),
            escapeshellarg($this->host),
            $snmpPort,
            escapeshellarg($oid)
        );
        $output = @shell_exec($cmd);
        if ($output && !str_contains($output, 'Timeout') && !str_contains($output, 'Error')) {
            if (preg_match('/=\s*\S+:\s*(.+)$/', trim($output), $m)) {
                return trim($m[1], '" ');
            }
            return trim($output);
        }
        return null;
    }

    public function snmpWalk(string $oid): array
    {
        $community = $this->device->snmp_community ?? $this->user ?: 'public';
        $snmpPort = $this->device->snmp_port ?? $this->port;

        if (function_exists('snmp2_walk')) {
            $result = @snmp2_walk("{$this->host}:{$snmpPort}", $community, $oid, 5000000, 1);
            return is_array($result) ? $result : [];
        }

        $cmd = sprintf(
            'snmpwalk -v2c -c %s -t 5 -r 1 %s:%d %s 2>&1',
            escapeshellarg($community),
            escapeshellarg($this->host),
            $snmpPort,
            escapeshellarg($oid)
        );
        $output = @shell_exec($cmd);
        if (!$output || str_contains($output, 'Timeout')) return [];

        $results = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (preg_match('/=\s*\S+:\s*(.+)$/', $line, $m)) {
                $results[] = trim($m[1], '" ');
            }
        }
        return $results;
    }

    protected function loginTelnet(): bool
    {
        $banner = $this->waitFor(['Username:', 'login:', 'Login:', 'User Name:', 'User:'], 10);

        if (stripos($banner, 'HTTP/') !== false || stripos($banner, 'GoAhead') !== false || stripos($banner, '<html') !== false) {
            $this->lastError = "Port {$this->port} is a web server, not Telnet. Use HTTP connection type instead.";
            return false;
        }

        if (empty($banner)) {
            $this->lastError = "No login prompt from {$this->host}:{$this->port} - port may not be Telnet";
            return false;
        }

        $this->send($this->user);

        $this->waitFor(['Password:', 'password:'], 10);
        $this->send($this->pass);

        $prompt = $this->waitFor(['>', '#', 'MA5680', 'OLT', 'BDCOM', 'ZTE', '$'], 10);

        if (stripos($prompt, 'incorrect') !== false || stripos($prompt, 'invalid') !== false || stripos($prompt, 'fail') !== false) {
            $this->lastError = "Login failed - invalid username or password";
            return false;
        }

        return !empty($prompt);
    }

    protected function send(string $command): void
    {
        if ($this->connectionType === self::CONN_SSH && $this->sshStream && $this->sshStream !== $this->connection) {
            fwrite($this->sshStream, $command . "\r\n");
        } elseif ($this->connection && is_resource($this->connection)) {
            fwrite($this->connection, $command . "\r\n");
        }
        usleep(100000);
    }

    protected function waitFor(array $patterns, int $timeout = 30): string
    {
        $buffer = '';
        $startTime = time();

        while (time() - $startTime < $timeout) {
            $char = @fread($this->connection, 1024);
            if ($char !== false && strlen($char) > 0) {
                $buffer .= $char;
                foreach ($patterns as $pattern) {
                    if (stripos($buffer, $pattern) !== false) {
                        return $buffer;
                    }
                }
            }
            usleep(50000);
        }

        return $buffer;
    }

    public function sendCommand(string $command, int $timeout = 30): string
    {
        if (!$this->connected && !$this->connect()) {
            return '';
        }

        if ($this->connectionType === self::CONN_HTTP) {
            return '';
        }

        if ($this->connectionType === self::CONN_SNMP) {
            return '';
        }

        $this->send($command);
        return $this->waitFor(['>', '#'], $timeout);
    }

    public function getOnuInfo(string $onuSerial): ?array
    {
        switch ($this->vendor) {
            case self::VENDOR_HUAWEI:
                return $this->getHuaweiOnuInfo($onuSerial);
            case self::VENDOR_ZTE:
                return $this->getZteOnuInfo($onuSerial);
            case self::VENDOR_BDCOM:
                return $this->getBdcomOnuInfo($onuSerial);
            case self::VENDOR_VSOL:
                return $this->getVsolOnuInfo($onuSerial);
            default:
                return $this->getGenericOnuInfo($onuSerial);
        }
    }

    protected function getHuaweiOnuInfo(string $onuSerial): ?array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        
        $output = $this->sendCommand("display ont info by-sn {$onuSerial}");
        
        $info = $this->parseHuaweiOutput($output);
        
        if (!empty($info)) {
            $signalOutput = $this->sendCommand("display ont optical-info {$info['port']} {$info['ont_id']}");
            $info['signal'] = $this->parseHuaweiSignal($signalOutput);
            $info = array_merge($info, $this->parseOpticalDiagnostics($signalOutput));
        }
        
        return $info;
    }

    protected function parseHuaweiOutput(string $output): array
    {
        $info = [];
        
        if (preg_match('/F\/S\/P\s*:\s*(\d+\/\d+\/\d+)/', $output, $matches)) {
            $info['port'] = $matches[1];
        }
        if (preg_match('/ONT-ID\s*:\s*(\d+)/', $output, $matches)) {
            $info['ont_id'] = $matches[1];
        }
        if (preg_match('/Run state\s*:\s*(\w+)/', $output, $matches)) {
            $info['status'] = strtolower($matches[1]) === 'online' ? 'online' : 'offline';
        }
        if (preg_match('/Last down time\s*:\s*(.+)/', $output, $matches)) {
            $info['last_down'] = trim($matches[1]);
        }
        if (preg_match('/Last down cause\s*:\s*(.+)/', $output, $matches)) {
            $info['down_cause'] = trim($matches[1]);
        }
        if (preg_match('/Description\s*:\s*(.+)/', $output, $matches)) {
            $info['description'] = trim($matches[1]);
        }
        
        return $info;
    }

    protected function parseHuaweiSignal(string $output): ?float
    {
        if (preg_match('/Rx optical power\(dBm\)\s*:\s*([-\d.]+)/', $output, $matches)) {
            return floatval($matches[1]);
        }
        return null;
    }

    protected function parseOpticalDiagnostics(string $output): array
    {
        $info = [];
        if (preg_match('/Temperature\s*\(?C?\)?\s*:\s*([-\d.]+)/i', $output, $m)) {
            $info['temperature'] = floatval($m[1]);
        }
        if (preg_match('/Voltage\s*\(?V?\)?\s*:\s*([-\d.]+)/i', $output, $m)) {
            $info['voltage'] = floatval($m[1]);
        }
        if (preg_match('/(?:OLT\s+)?Rx\s+(?:ONT\s+)?optical\s+power\s*\(dBm\)\s*:\s*([-\d.]+)/i', $output, $m)) {
            $info['tx_power'] = floatval($m[1]);
        }
        if (preg_match('/Tx\s+(?:optical\s+)?power\s*\(dBm\)\s*:\s*([-\d.]+)/i', $output, $m)) {
            $info['tx_power_onu'] = floatval($m[1]);
        }
        if (preg_match('/Bias\s*(?:current)?\s*\(?mA?\)?\s*:\s*([-\d.]+)/i', $output, $m)) {
            $info['tx_bias'] = floatval($m[1]);
        }
        if (preg_match('/Distance\s*\(?(?:m|km)?\)?\s*:\s*([-\d.]+)/i', $output, $m)) {
            $info['distance'] = floatval($m[1]);
        }
        if (preg_match('/ONU\s+Distance\s*\(?m\)?\s*:\s*([-\d.]+)/i', $output, $m)) {
            $info['distance'] = floatval($m[1]);
        }
        if (preg_match('/VLAN\s*(?:ID)?\s*:\s*(\d+)/i', $output, $m)) {
            $info['vlan'] = intval($m[1]);
        }
        if (preg_match('/MAC\s*(?:address)?\s*:\s*([0-9A-Fa-f:.-]+)/i', $output, $m)) {
            $info['mac'] = trim($m[1]);
        }
        return $info;
    }

    protected function getZteOnuInfo(string $onuSerial): ?array
    {
        $this->sendCommand('enable');
        $this->sendCommand('configure terminal');
        
        $output = $this->sendCommand("show gpon onu by sn {$onuSerial}");
        
        $info = $this->parseZteOutput($output);
        if (!empty($info)) {
            $info = array_merge($info, $this->parseOpticalDiagnostics($output));
        }
        return $info;
    }

    protected function parseZteOutput(string $output): array
    {
        $info = [];
        
        if (preg_match('/gpon-onu_(\d+\/\d+\/\d+):(\d+)/', $output, $matches)) {
            $info['port'] = $matches[1];
            $info['ont_id'] = $matches[2];
        }
        if (preg_match('/Status\s*:\s*(\w+)/', $output, $matches)) {
            $info['status'] = strtolower($matches[1]);
        }
        if (preg_match('/RX\s*:\s*([-\d.]+)\s*dBm/', $output, $matches)) {
            $info['signal'] = floatval($matches[1]);
        }
        
        return $info;
    }

    protected function getBdcomOnuInfo(string $onuSerial): ?array
    {
        if ($this->connectionType === self::CONN_HTTP) {
            return $this->getBdcomOnuInfoHttp($onuSerial);
        }

        if ($this->connectionType === self::CONN_SNMP) {
            return $this->getBdcomOnuInfoSnmp($onuSerial);
        }

        $this->sendCommand('enable', 5);

        // Step 1: get all ONUs via telnet to find the one matching this serial
        $onuListOut = $this->sendCommand('show epon onu-information', 15);
        $allOnus    = $this->parseBdcomOnusTelnet($onuListOut);

        $serialClean = strtoupper(str_replace(['.', ':', '-', 'BDCOM'], '', $onuSerial));
        $matched = null;
        foreach ($allOnus as $o) {
            $snClean = strtoupper(str_replace(['.', ':', '-', 'BDCOM'], '', $o['serial_number']));
            $macClean = strtoupper(str_replace(['.', ':', '-'], '', $o['mac_address']));
            if ($snClean === $serialClean || $macClean === $serialClean) {
                $matched = $o;
                break;
            }
        }

        if (!$matched) {
            // Try DB lookup by serial to know the port
            $dbOnu = \DB::table('onus')->where('serial_number', $onuSerial)
                ->orWhere('mac_address', $onuSerial)->first();
            if ($dbOnu) {
                $matched = [
                    'port'       => $dbOnu->pon_port,
                    'onu_number' => $dbOnu->onu_number,
                    'status'     => $dbOnu->status,
                    'mac_address'=> $dbOnu->mac_address,
                    'model'      => $dbOnu->model,
                    'vendor'     => null,
                ];
            }
        }

        if (!$matched || empty($matched['port'])) {
            return null;
        }

        // Step 2: get optical transceiver data for the specific port
        preg_match('/(\d+)$/', $matched['port'], $pm);
        $portNum = $pm[1] ?? 2;
        $optOut  = $this->sendCommand("show epon onu-ctc-optical-transceiver-diagnosis interface epon 0/{$portNum}", 10);
        $allOptical = $this->parseBdcomOpticalOutput($optOut);
        $intfKey = strtoupper($matched['port']) . ':' . $matched['onu_number'];
        $opt = $allOptical[$intfKey] ?? null;

        // Step 3: get ONU distance (try multiple commands)
        $distance = null;
        $onuNum   = $matched['onu_number'];
        try {
            // Primary command
            $distOut = $this->sendCommand("show epon onu-ctc-distance interface epon 0/{$portNum}", 8);
            if (!str_contains($distOut, 'Unknown command') && !str_contains($distOut, 'Invalid')) {
                $distance = $this->parseBdcomDistance($distOut, $onuNum);
            }

            // Backup: show epon onu-distance interface epon 0/X
            if ($distance === null) {
                $distOut2 = $this->sendCommand("show epon onu-distance interface epon 0/{$portNum}", 6);
                if (!str_contains($distOut2, 'Unknown command') && !str_contains($distOut2, 'Invalid')) {
                    $distance = $this->parseBdcomDistance($distOut2, $onuNum);
                }
            }

            // Backup 2: show epon onu-information interface epon 0/X:onuNum
            if ($distance === null) {
                $infoOut = $this->sendCommand("show epon onu-information interface epon 0/{$portNum}:{$onuNum}", 8);
                if (!str_contains($infoOut, 'Unknown command') && !str_contains($infoOut, 'Invalid')) {
                    $distance = $this->parseBdcomDistance($infoOut, $onuNum);
                    // Also try generic distance pattern in that output
                    if ($distance === null && preg_match('/Distance.*?(\d+)\s*m/i', $infoOut, $dm)) {
                        $distance = (float)$dm[1];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug("BDCOM distance fetch error: " . $e->getMessage());
        }
        Log::info("BDCOM ONU distance for port {$portNum} ONU#{$onuNum}: " . ($distance ?? 'N/A'));

        $downReason = $matched['down_reason'] ?? null;
        if (($matched['status'] ?? '') === 'offline') {
            $inactiveReasons = $this->getBdcomInactiveOnuReasons();
            $irKey = strtoupper($matched['port']) . ':' . $matched['onu_number'];
            if (isset($inactiveReasons[$irKey])) {
                $downReason = $inactiveReasons[$irKey];
            }
        }

        return [
            'port'        => $matched['port'],
            'onu_number'  => $matched['onu_number'],
            'status'      => $matched['status'],
            'down_reason' => $downReason,
            'model'       => $matched['model'] ?? null,
            'vendor'      => $matched['vendor'] ?? null,
            'mac_address' => $matched['mac_address'] ?? null,
            'rx_power'    => $opt['rx_power'] ?? null,
            'tx_power'    => $opt['tx_power'] ?? null,
            'signal'      => $opt['rx_power'] ?? null,
            'temperature' => $opt['temperature'] ?? null,
            'voltage'     => $opt['voltage'] ?? null,
            'tx_bias'     => $opt['tx_bias'] ?? null,
            'distance'    => $distance,
        ];
    }

    protected function getBdcomOnuInfoSnmp(string $onuSerial): ?array
    {
        try {
            $snmp    = \App\Services\OLT\SNMPClient::fromDevice($this->device);
            $mapping = new \App\Services\OLT\BdcomOIDMapping();

            $macClean = strtoupper(preg_replace('/[^0-9A-F]/i', '', str_ireplace('BDCOM', '', $onuSerial)));

            $macTable     = $snmp->walk($mapping->getOnuMacOid());
            $targetSuffix = null;

            foreach ($macTable as $fullOid => $rawMac) {
                $parsed    = $mapping->parseMacAddress($rawMac);
                $macParsed = strtoupper(preg_replace('/[^0-9A-F]/', '', $parsed));
                if ($macParsed === $macClean) {
                    $baseOid      = $mapping->getOnuMacOid();
                    $targetSuffix = substr($fullOid, strlen($baseOid));
                    break;
                }
            }

            if (!$targetSuffix) {
                \Illuminate\Support\Facades\Log::warning("BDCOM SNMP: ONU not found for serial {$onuSerial}");
                return null;
            }

            $idxParts = $mapping->parseOnuIndex($targetSuffix);
            $portIdx  = $idxParts['port_index'];
            $onuIdx   = $idxParts['onu_index'];

            $statusRaw = $snmp->get($mapping->getOnuStatusOid() . $targetSuffix);
            $status    = $statusRaw !== null ? $mapping->parseOnuStatus($statusRaw) : 'online';

            $rxRaw   = $snmp->get($mapping->getOnuRxPowerOid() . $targetSuffix);
            $rxPower = $rxRaw !== null ? round(intval($rxRaw) / 10, 2) : null;

            $txRaw   = $snmp->get($mapping->getOnuTxPowerOid() . $targetSuffix);
            $txPower = $txRaw !== null ? round(intval($txRaw) / 10, 2) : null;

            $tempRaw     = $snmp->get(\App\Services\OLT\BdcomOIDMapping::EPON_ONU_TEMPERATURE . $targetSuffix);
            $temperature = $tempRaw !== null ? round(intval($tempRaw) / 1000, 2) : null;

            $voltRaw = $snmp->get(\App\Services\OLT\BdcomOIDMapping::EPON_ONU_VOLTAGE . $targetSuffix);
            $voltage = $voltRaw !== null ? round(intval($voltRaw) / 2000, 2) : null;

            $biasRaw = $snmp->get(\App\Services\OLT\BdcomOIDMapping::EPON_ONU_TX_BIAS . $targetSuffix);
            $txBias  = $biasRaw !== null ? round(intval($biasRaw) / 1000, 2) : null;

            $distRaw  = $snmp->get(\App\Services\OLT\BdcomOIDMapping::EPON_ONU_DISTANCE . $targetSuffix);
            $distance = $distRaw !== null ? intval($distRaw) : null;

            \Illuminate\Support\Facades\Log::info("BDCOM SNMP: serial={$onuSerial}, port={$portIdx}, onu={$onuIdx}, status={$status}, rx={$rxPower}, dist={$distance}m");

            return [
                'port'        => $mapping->formatPortName($portIdx),
                'onu_number'  => $onuIdx,
                'status'      => $status,
                'mac_address' => null,
                'rx_power'    => $rxPower,
                'tx_power'    => $txPower,
                'signal'      => $rxPower,
                'temperature' => $temperature,
                'voltage'     => $voltage,
                'tx_bias'     => $txBias,
                'distance'    => $distance,
                'source'      => 'snmp',
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("BDCOM SNMP getOnuInfo failed: " . $e->getMessage());
            return null;
        }
    }

        protected function getBdcomOnusSnmp(): array
    {
        try {
            $snmp = \App\Services\OLT\SNMPClient::fromDevice($this->device);

            // BDCOM optical info table (.10.1.1) — only online ONUs appear here
            $macOid    = '.1.3.6.1.4.1.3320.101.10.1.1.3';
            $distOid   = '.1.3.6.1.4.1.3320.101.10.1.1.27';
            $vendorOid = '.1.3.6.1.4.1.3320.101.10.1.1.1';
            $modelOid  = '.1.3.6.1.4.1.3320.101.10.1.1.2';
            // BDCOM DDM table (.10.5.1) — power in dBm (integer, no divide needed)
            $rxOid   = '.1.3.6.1.4.1.3320.101.10.5.1.5';
            $txOid   = '.1.3.6.1.4.1.3320.101.10.5.1.6';
            $tempOid = '.1.3.6.1.4.1.3320.101.10.5.1.3';

            $macTable = $snmp->walk($macOid);
            if (empty($macTable)) {
                \Illuminate\Support\Facades\Log::warning("BDCOM SNMP getAllOnus: optical MAC table empty for {$this->host}");
                return [];
            }

            $distTable   = $snmp->walk($distOid);
            $vendorTable = $snmp->walk($vendorOid);
            $modelTable  = $snmp->walk($modelOid);
            $rxTable     = $snmp->walk($rxOid);
            $txTable     = $snmp->walk($txOid);
            $tempTable   = $snmp->walk($tempOid);
            $voltOid  = '.1.3.6.1.4.1.3320.101.10.5.1.4';
            $biasOid  = '.1.3.6.1.4.1.3320.101.10.5.1.2';
            $voltTable = $snmp->walk($voltOid);
            $biasTable = $snmp->walk($biasOid);

            // SNMP ONU state table (.11.1.1.6): 2=deregistered/offline, 5=auto-configured/online
            // OID index format: {ifIndex}.{b1}.{b2}.{b3}.{b4}.{b5}.{b6} (MAC bytes as decimal)
            $bdStOid   = '.1.3.6.1.4.1.3320.101.11.1.1.6';
            $bdStTable = $snmp->walk($bdStOid);
            $macStatusMap = [];
            foreach ($bdStTable as $bdStFullOid => $bdStVal) {
                // Extract the last 7 dot-separated integers (ifIndex + 6 MAC bytes)
                $bdParts = explode('.', $bdStFullOid);
                if (count($bdParts) >= 7) {
                    $bdMacBytes = array_slice($bdParts, -6);
                    $bdMacKey   = strtoupper(vsprintf('%02X:%02X:%02X:%02X:%02X:%02X', $bdMacBytes));
                    $macStatusMap[$bdMacKey] = (int)$bdStVal;
                }
            }

            $mapping = new \App\Services\OLT\BdcomOIDMapping();
            $onus    = [];

            // Detect SNMP->EPON port offset.
            // BDCOM numbers EPON ports starting at portIdx=3 internally
            // (GE ports occupy 0-2). Subtract (minPortIdx - 1) so that
            // physical epon0/1 is saved as EPON0/1 in the database.
            $__pIdxs = [];
            foreach (array_keys($macTable) as $__oid) {
                if (preg_match('/\.([0-9]+)$/', $__oid, $__mm)) {
                    $__pIdxs[] = (int)((((int)$__mm[1]) - 1) / 32);
                }
            }
            $portOffset = !empty($__pIdxs) ? (min($__pIdxs) - 1) : 0;
            unset($__pIdxs, $__oid, $__mm);

            foreach ($macTable as $fullOid => $rawMac) {
                // BDCOM uses single trailing integer index, e.g. .33 or .34
                if (!preg_match('/\\.(\\d+)$/', $fullOid, $m)) continue;
                $idx = (int)$m[1];

                // Decode port/ONU: BDCOM EPON supports 32 ONUs per port
                $portIdx = (int)(($idx - 1) / 32) - $portOffset;
                $onuIdx  = ($idx - 1) % 32 + 1;

                $mac    = $mapping->parseMacAddress($rawMac);
                $serial = 'BDCOM' . strtoupper(preg_replace('/[^0-9A-F]/i', '', $mac));

                $distRaw   = $distTable["{$distOid}.{$idx}"]    ?? null;
                $vendorRaw = $vendorTable["{$vendorOid}.{$idx}"] ?? null;
                $modelRaw  = $modelTable["{$modelOid}.{$idx}"]   ?? null;
                $rxRaw     = $rxTable["{$rxOid}.{$idx}"]        ?? null;
                $txRaw     = $txTable["{$txOid}.{$idx}"]        ?? null;
                $tempRaw   = $tempTable["{$tempOid}.{$idx}"]    ?? null;
                $voltRaw   = $voltTable["{$voltOid}.{$idx}"]   ?? null;
                $biasRaw   = $biasTable["{$biasOid}.{$idx}"]   ?? null;

                // Skip ghost/placeholder ONUs (no real data)
                if (trim((string)$vendorRaw) === '----' && (int)$distRaw === 0 && $rxRaw === null) {
                    \Illuminate\Support\Facades\Log::info("BDCOM SNMP: Skipping ghost ONU idx={$idx} (vendor='----', distance=0, no RX)");
                    continue;
                }

                $onus[] = [
                    'port'          => "EPON0/{$portIdx}",
                    'onu_number'    => $onuIdx,
                    'serial_number' => $serial,
                    'mac_address'   => $mac,
                    'status'        => (($macStatusMap[$mac] ?? 5) === 2) ? 'offline' : 'online',
                    'rx_power'      => ($rxRaw   !== null && $rxRaw   !== '') ? round((float)$rxRaw   / 10, 2) : null,
                    'tx_power'      => ($txRaw   !== null && $txRaw   !== '') ? round((float)$txRaw   / 10, 2) : null,
                    'distance'      => ($distRaw !== null && $distRaw !== '') ? (int)$distRaw   : null,
                    'temperature'   => ($tempRaw !== null && $tempRaw !== '') ? round((float)$tempRaw / 1000, 2) : null,
                    'voltage'       => ($voltRaw !== null && $voltRaw !== '') ? round((float)$voltRaw / 2000, 2) : null,
                    'tx_bias'       => ($biasRaw !== null && $biasRaw !== '') ? round((float)$biasRaw / 1000, 2) : null,
                    'model'         => trim(preg_replace('/[^\\x20-\\x7E]/', '', (string)$modelRaw)),
                    'vendor'        => trim(preg_replace('/[^\\x20-\\x7E]/', '', (string)$vendorRaw)) ?: 'BDCOM',
                    'down_reason'   => (($macStatusMap[$mac] ?? 5) === 2) ? 'power-off' : null,
                    'source'        => 'snmp',
                ];
            }

            \Illuminate\Support\Facades\Log::info("BDCOM SNMP getAllOnus: found " . count($onus) . " ONUs on {$this->host}");

            // Auto-detect total_ports: use max EPON port found in ONU data (most reliable).
            // The SNMP interface-table OID can include GE/uplink ports and inflate the count.
            try {
                if (!empty($onus)) {
                    $detectedMax = max(array_map(function ($o) {
                        if (preg_match('/([0-9]+)$/', $o['port'] ?? '', $pm)) return (int)$pm[1];
                        return 0;
                    }, $onus));
                    if ($detectedMax > 0 && $detectedMax !== (int)($this->device->total_ports ?? 0)) {
                        $this->device->update(['total_ports' => $detectedMax]);
                        \Illuminate\Support\Facades\Log::info("BDCOM SNMP: auto-updated total_ports to {$detectedMax} (max ONU port) for {$this->host}");
                    }
                } else {
                    // Fallback when no ONUs found: walk EPON port ONU-count table (one row per EPON port)
                    $epPortOid = '.1.3.6.1.4.1.3320.101.12.1.1.5';
                    $epPortData = $snmp->walk($epPortOid);
                    if (!empty($epPortData)) {
                        $physicalPortCount = count($epPortData);
                        if ($physicalPortCount > 0 && $physicalPortCount !== (int)($this->device->total_ports ?? 0)) {
                            $this->device->update(['total_ports' => $physicalPortCount]);
                            \Illuminate\Support\Facades\Log::info("BDCOM SNMP: auto-updated total_ports to {$physicalPortCount} (EPON port OID) for {$this->host}");
                        }
                    }
                }
            } catch (\Throwable $portEx) {
                \Illuminate\Support\Facades\Log::warning("BDCOM SNMP: port count detection failed: " . $portEx->getMessage());
            }

            return $onus;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("BDCOM SNMP getAllOnus failed: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Sync ONUs from a BDCOM GPON OLT via standard SNMP interface table.
     * Uses ifName (GPON0/x:y), ifOperStatus, and ifPhysAddress.
     * Serial numbers are derived from the stable ifIndex since BDCOM GPON
     * does not expose ONU serial numbers via SNMP on this firmware.
     */
    protected function getBdcomGponOnusSnmp(): array
    {
        try {
            $snmp = \App\Services\OLT\SNMPClient::fromDevice($this->device);

            // ifName: .1.3.6.1.2.1.31.1.1.1.1.{ifIndex} => "GPON0/x:y"
            $ifNameTable   = $snmp->walk('.1.3.6.1.2.1.31.1.1.1.1');
            // ifOperStatus: 1=up(online), 2=down(offline)
            $ifStatusTable = $snmp->walk('.1.3.6.1.2.1.2.2.1.8');
            // ifPhysAddress (MAC) per interface
            $ifMacTable    = $snmp->walk('.1.3.6.1.2.1.2.2.1.6');

            if (empty($ifNameTable)) {
                \Illuminate\Support\Facades\Log::warning("BDCOM GPON SNMP: ifName table empty for {$this->host}:{$this->port}");
                return [];
            }

            // Build index-keyed lookup maps
            $statusMap = [];
            foreach ($ifStatusTable as $oid => $val) {
                if (preg_match('/\.([0-9]+)$/', $oid, $m)) {
                    $statusMap[(int)$m[1]] = (int)$val;
                }
            }
            $macMap = [];
            foreach ($ifMacTable as $oid => $val) {
                if (preg_match('/\.([0-9]+)$/', $oid, $m)) {
                    $macMap[(int)$m[1]] = $val;
                }
            }

            $onus = [];
            foreach ($ifNameTable as $oid => $ifName) {
                $ifName = trim((string)$ifName);
                // Match GPON0/port:onuNum  (e.g. GPON0/1:3)
                if (!preg_match('/^GPON(\d+)\/(\d+):(\d+)$/i', $ifName, $pm)) {
                    continue;
                }
                $slotNum = (int)$pm[1]; // always 0 for most BDCOM
                $portNum = (int)$pm[2];
                $onuNum  = (int)$pm[3];
                $port    = 'GPON' . $slotNum . '/' . $portNum;

                // Extract ifIndex from OID tail
                preg_match('/\.([0-9]+)$/', $oid, $im);
                $ifIdx = (int)($im[1] ?? 0);

                // Status: 1=up, 2=down
                $opStatus = $statusMap[$ifIdx] ?? 2;
                $status   = ($opStatus === 1) ? 'online' : 'offline';

                // MAC address (may be OLT MAC, not ONU MAC for GPON)
                $macRaw = $macMap[$ifIdx] ?? null;
                $mac    = null;
                if ($macRaw) {
                    $hexBytes = array_map('trim', explode(' ', trim((string)$macRaw)));
                    if (count($hexBytes) >= 6) {
                        $mac = strtoupper(implode(':', $hexBytes));
                    }
                }

                // Stable serial: GPON + zero-padded ifIndex (unique per ONU slot)
                $serial = 'GPON' . str_pad($ifIdx, 8, '0', STR_PAD_LEFT);

                $onus[] = [
                    'serial_number' => $serial,
                    'port'          => $port,
                    'pon_port'      => $port,
                    'onu_number'    => $onuNum,
                    'mac_address'   => $mac,
                    'status'        => $status,
                    'rx_power'      => null,
                    'tx_power'      => null,
                    'distance'      => null,
                    'model'         => null,
                    'vendor'        => 'BDCOM',
                    'down_reason'   => ($status === 'offline') ? 'power-off' : null,
                    'source'        => 'snmp',
                ];
            }

            \Illuminate\Support\Facades\Log::info("BDCOM GPON SNMP: found " . count($onus) . " ONUs on {$this->host}:{$this->port}");

            // Auto-detect physical GPON port count
            try {
                $physPorts = 0;
                foreach ($ifNameTable as $oid => $name) {
                    // Physical GPON ports match GPON0/x without colon
                    if (preg_match('/^GPON\d+\/\d+$/i', trim((string)$name))) {
                        $physPorts++;
                    }
                }
                if ($physPorts > 0 && $physPorts !== (int)($this->device->total_ports ?? 0)) {
                    $this->device->update(['total_ports' => $physPorts]);
                    \Illuminate\Support\Facades\Log::info("BDCOM GPON SNMP: auto-updated total_ports to {$physPorts} for {$this->host}");
                }
            } catch (\Throwable $ignored) {}

            return $onus;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("BDCOM GPON SNMP getAllOnus failed: " . $e->getMessage());
            return [];
        }
    }

    protected function getBdcomOnuInfoHttp(string $onuSerial): ?array
    {
        $allOnus = $this->getBdcomOnusHttp();

        $serialClean = strtoupper(str_replace(['.', ':', '-'], '', $onuSerial));
        foreach ($allOnus as $onu) {
            $onuSerialClean = strtoupper(str_replace(['.', ':', '-'], '', $onu['serial_number']));
            if ($onuSerialClean === $serialClean) {
                return [
                    'port' => $onu['port'] ?? null,
                    'ont_id' => $onu['onu_number'] ?? null,
                    'status' => $onu['status'] ?? 'unknown',
                    'model' => $onu['model'] ?? null,
                    'vendor' => $onu['vendor'] ?? null,
                    'mac_address' => $onu['mac_address'] ?? null,
                    'description' => $onu['description'] ?? null,
                    'serial_number' => $onu['serial_number'],
                    'rx_power' => $onu['rx_power'] ?? null,
                    'tx_power' => $onu['tx_power'] ?? null,
                    'signal' => $onu['rx_power'] ?? null,
                    'interface' => $onu['interface'] ?? null,
                ];
            }
        }

        return null;
    }

    protected function parseBdcomOutput(string $output): array
    {
        $info = [];
        
        if (preg_match('/EPON(\d+\/\d+):(\d+)/', $output, $matches)) {
            $info['port'] = $matches[1];
            $info['ont_id'] = $matches[2];
        }
        if (preg_match('/Status\s*:\s*(\w+)/i', $output, $matches)) {
            $info['status'] = strtolower($matches[1]);
        }
        if (preg_match('/Rx Power\s*:\s*([-\d.]+)/i', $output, $matches)) {
            $info['signal'] = floatval($matches[1]);
        }
        
        return $info;
    }

    protected function getVsolOnuInfo(string $onuSerial): ?array
    {
        $this->sendCommand('enable');
        
        $output = $this->sendCommand("show onu info sn {$onuSerial}");
        
        $info = $this->parseVsolOutput($output);
        if (!empty($info)) {
            $info = array_merge($info, $this->parseOpticalDiagnostics($output));
        }
        return $info;
    }

    protected function parseVsolOutput(string $output): array
    {
        $info = [];
        
        if (preg_match('/Port\s*:\s*(\d+\/\d+)/i', $output, $matches)) {
            $info['port'] = $matches[1];
        }
        if (preg_match('/ONU ID\s*:\s*(\d+)/i', $output, $matches)) {
            $info['ont_id'] = $matches[1];
        }
        if (preg_match('/State\s*:\s*(\w+)/i', $output, $matches)) {
            $info['status'] = strtolower($matches[1]);
        }
        if (preg_match('/Rx Power\s*:\s*([-\d.]+)/i', $output, $matches)) {
            $info['signal'] = floatval($matches[1]);
        }
        if (preg_match('/Last Down\s*:\s*(.+)/i', $output, $matches)) {
            $info['last_down'] = trim($matches[1]);
        }
        
        return $info;
    }

    protected function getGenericOnuInfo(string $onuSerial): ?array
    {
        $output = $this->sendCommand("show onu {$onuSerial}");
        
        $info = [
            'serial' => $onuSerial,
            'status' => stripos($output, 'online') !== false ? 'online' : 'offline',
        ];
        
        if (preg_match('/([-\d.]+)\s*dBm/i', $output, $matches)) {
            $info['signal'] = floatval($matches[1]);
        }
        
        return $info;
    }

    public function getOnuStatus(string $onuSerial): string
    {
        $info = $this->getOnuInfo($onuSerial);
        return $info['status'] ?? 'unknown';
    }

    public function getOnuSignal(string $onuSerial): ?float
    {
        $info = $this->getOnuInfo($onuSerial);
        return $info['signal'] ?? null;
    }

    public function rebootOnu(string $port, string $onuId): bool
    {
        switch ($this->vendor) {
            case self::VENDOR_HUAWEI:
                $this->sendCommand('enable', 5);
                $this->sendCommand('config', 5);
                $this->sendCommand("ont reset {$port} {$onuId}");
                break;
            case self::VENDOR_ZTE:
                $this->sendCommand('enable', 5);
                $this->sendCommand('configure terminal', 5);
                $this->sendCommand("onu reset gpon-onu_{$port}:{$onuId}");
                break;
            case self::VENDOR_BDCOM:
                $this->sendCommand('enable', 5);
                $intf = "EPON0/{$port}:{$onuId}";
                $cmd = "epon reboot onu interface EPON0/{$port}:{$onuId}";
                $output = $this->sendCommand($cmd, 10);
                Log::info("BDCOM reboot [{$cmd}]: {$output}");
                if (preg_match('/Unknown command|Invalid|%Error|Incomplete/i', $output)) {
                    Log::error("BDCOM reboot failed for ONU {$intf}: {$output}");
                    return false;
                }
                break;
            default:
                $this->sendCommand("onu reset {$port} {$onuId}");
        }
        return true;
    }

    public function getAllOnus(): array
    {
        if ($this->connectionType === self::CONN_HTTP && $this->vendor === self::VENDOR_BDCOM) {
            return $this->getBdcomOnusHttp();
        }

        if ($this->vendor === self::VENDOR_BDCOM && $this->connectionType === self::CONN_SNMP) {
            // Route to GPON handler if pon_type is gpon
            if (isset($this->device) && strtolower($this->device->pon_type ?? '') === 'gpon') {
                return $this->getBdcomGponOnusSnmp();
            }
            return $this->getBdcomOnusSnmp();
        }

        if ($this->vendor === self::VENDOR_BDCOM) {
            // GPON telnet/SSH: route to GponTelnetCollector
            if (isset($this->device) && strtolower($this->device->pon_type ?? "") === "gpon") {
                $this->disconnect();
                $collector = new \App\Services\OLT\GponTelnetCollector($this->device);
                return $collector->collectAll();
            }
            $this->sendCommand('enable', 5);
            $output = $this->sendCommand('show epon onu-information', 15);
            $onus = $this->parseBdcomOnusTelnet($output);

            $optical = $this->getBdcomOpticalData();
            $inactiveReasons = $this->getBdcomInactiveOnuReasons();
            foreach ($onus as &$onu) {
                $key = ($onu['port'] ?? '') . ':' . ($onu['onu_number'] ?? '');
                if (($onu['status'] ?? '') === 'offline' && isset($inactiveReasons[$key])) {
                    $onu['down_reason'] = $inactiveReasons[$key];
                }
                if (isset($optical[$key])) {
                    $opt = $optical[$key];
                    $onu['rx_power']    = $opt['rx_power'] ?? null;
                    $onu['tx_power']    = $opt['tx_power'] ?? null;
                    $onu['temperature'] = $opt['temperature'] ?? null;
                    $onu['voltage']     = $opt['voltage'] ?? null;
                    $onu['tx_bias']     = $opt['tx_bias'] ?? null;
                }

                $portNum = '';
                if (preg_match('/EPON(\d+\/\d+)/i', $onu['port'] ?? '', $pm)) {
                    $portNum = $pm[1];
                }
                if ($portNum && isset($onu['onu_number'])) {
                    $cfgOut = $this->sendCommand("show running-config interface epon {$portNum}:{$onu['onu_number']}", 5);
                    if (preg_match('/^\s*description\s+(.+)/mi', $cfgOut, $dm)) {
                        $onu['description'] = trim($dm[1]);
                    }
                }
            }
            unset($onu);

            return $onus;
        }

        switch ($this->vendor) {
            case self::VENDOR_HUAWEI:
                $output = $this->sendCommand('display ont info summary');
                break;
            case self::VENDOR_ZTE:
                $output = $this->sendCommand('show gpon onu state');
                break;
            default:
                $output = $this->sendCommand('show onu all');
        }

        return [];
    }

    protected function parseBdcomOnusTelnet(string $output): array
    {
        $onus = [];
        $lines = explode("\n", $output);
        $pending = null;

        foreach ($lines as $line) {
            $trimmed = rtrim($line);

            // Start of an ONU entry
            if (preg_match('/^(EPON\d+\/\d+):(\d+)\s+(\S+)\s+(\S+)\s+((?:[0-9a-fA-F]{4}\.){2}[0-9a-fA-F]{4})\s+(.*)/', $trimmed, $m)) {
                if ($pending !== null) {
                    $onus[] = $pending;
                }
                $macRaw = $m[5];
                $mac = strtoupper(str_replace('.', '', $macRaw));
                $mac = implode(':', str_split($mac, 2));
                $serial = 'BDCOM' . strtoupper(str_replace('.', '', $macRaw));
                $pending = [
                    'interface'    => $m[1] . ':' . $m[2],
                    'port'         => $m[1],
                    'onu_number'   => (int)$m[2],
                    'vendor'       => $m[3] !== 'N/A' ? $m[3] : null,
                    'model'        => $m[4] !== 'N/A' ? $m[4] : null,
                    'mac_address'  => $mac,
                    'serial_number'=> $serial,
                    'description'  => null,
                    'status'       => 'online',
                ];
                // Status may be on same line after description
                $rest = trim($m[6]);
                if (preg_match('/\s+(static|dynamic)\s+(\S+)/i', $rest, $sm)) {
                    $normalized = self::normalizeOnuStatus($sm[2]);
                    $pending['status']      = $normalized['status'];
                    $pending['down_reason'] = $normalized['down_reason'];
                }
            } elseif ($pending !== null && preg_match('/^\s+(static|dynamic)\s+(\S+)/i', $trimmed, $sm)) {
                // Continuation line with bind_type and status
                $normalized = self::normalizeOnuStatus($sm[2]);
                $pending['status']      = $normalized['status'];
                $pending['down_reason'] = $normalized['down_reason'];
                $onus[] = $pending;
                $pending = null;
            }
        }
        if ($pending !== null) {
            $onus[] = $pending;
        }

        Log::info("BDCOM Telnet: parsed " . count($onus) . " ONUs from show epon onu-information");
        return $onus;
    }

    protected function getBdcomInactiveOnuReasons(): array
    {
        $reasons = [];
        try {
            $this->sendCommand('terminal length 0', 3);
            $this->sendCommand('terminal width 256', 3);

            $output = $this->sendCommand('show epon inactive-onu', 10);
            Log::debug('BDCOM inactive-onu raw output (wide):', ['output' => $output]);
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (preg_match('/^(EPON\d+\/\d+):(\d+)\s+/i', $trimmed, $m)) {
                    $key = strtoupper($m[1]) . ':' . $m[2];
                    $reason = $this->parseBdcomInactiveReason($trimmed);
                    $reasons[$key] = $reason;
                    Log::debug("BDCOM inactive reason: {$key} => {$reason} | line: {$trimmed}");
                }
            }

            if (empty($reasons) || $this->allReasonsGeneric($reasons)) {
                $altCmds = [
                    'show epon onu-ctc-llid-deregister',
                    'show epon onu offline-reason',
                ];
                foreach ($altCmds as $cmd) {
                    $output2 = $this->sendCommand($cmd, 10);
                    Log::debug("BDCOM alt reason cmd [{$cmd}] output:", ['output' => $output2]);
                    if (str_contains($output2, 'Unknown command') || str_contains($output2, 'Invalid') || str_contains($output2, '%Error')) {
                        continue;
                    }
                    $lines2 = explode("\n", $output2);
                    foreach ($lines2 as $line) {
                        $trimmed = trim($line);
                        if (preg_match('/^(EPON\d+\/\d+):(\d+)\s+/i', $trimmed, $m)) {
                            $key = strtoupper($m[1]) . ':' . $m[2];
                            $reason = $this->parseBdcomInactiveReason($trimmed);
                            if ($reason !== 'offline') {
                                $reasons[$key] = $reason;
                                Log::debug("BDCOM alt reason: {$key} => {$reason} | line: {$trimmed}");
                            }
                        }
                    }
                    if (!$this->allReasonsGeneric($reasons)) break;
                }
            }

            foreach ($reasons as $key => &$reason) {
                if ($reason === 'offline') {
                    $parts = explode(':', $key);
                    if (count($parts) === 2) {
                        $portPart = $parts[0];
                        $onuNum = $parts[1];
                        preg_match('/(\d+)$/', $portPart, $pm);
                        $pNum = $pm[1] ?? '2';
                        $detailOut = $this->sendCommand("show epon onu-information interface epon 0/{$pNum}:{$onuNum}", 8);
                        Log::debug("BDCOM per-ONU detail for {$key}:", ['output' => $detailOut]);
                        $detailReason = $this->parseBdcomInactiveReason($detailOut);
                        if ($detailReason !== 'offline') {
                            $reason = $detailReason;
                        }
                    }
                }
            }
            unset($reason);
        } catch (\Throwable $e) {
            Log::warning('BDCOM inactive-onu fetch failed: ' . $e->getMessage());
        }
        Log::info("BDCOM: parsed " . count($reasons) . " inactive ONU reasons");
        return $reasons;
    }

    protected function allReasonsGeneric(array $reasons): bool
    {
        foreach ($reasons as $r) {
            if ($r !== 'offline') return false;
        }
        return true;
    }

    protected function parseBdcomInactiveReason(string $line): string
    {
        if (preg_match('/DyingGasp/i', $line)) {
            return 'power-off';
        }
        if (preg_match('/power.?off|power.?down|power.?fail/i', $line)) {
            return 'power-off';
        }
        if (preg_match('/mpcp.?down|mpcp.?timeout/i', $line)) {
            return 'wire-down';
        }
        if (preg_match('/oam.?disc/i', $line)) {
            return 'wire-down';
        }
        if (preg_match('/\blos\b/i', $line)) {
            return 'los';
        }
        if (preg_match('/wire.?down|link.?down|fiber/i', $line)) {
            return 'wire-down';
        }
        return 'offline';
    }

    protected function parseBdcomDistance(string $out, int $onuNumber): ?float
    {
        Log::debug("BDCOM distance raw output for ONU#{$onuNumber}:", ['out' => $out]);
        foreach (explode("\n", $out) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Format A: "epon0/2:1  1050" (interface:id then a number)
            if (preg_match('/epon\d+\/\d+:' . $onuNumber . '\b\s+(\d+)/i', $line, $m)) {
                $val = (float)$m[1];
                if ($val > 0 && $val < 100000) return $val;
            }
            // Format B: "EPON0/2:1  dynamic  online  1050"
            if (preg_match('/epon\d+\/\d+:' . $onuNumber . '\b.*?(\d{2,6})\s*(?:m)?\s*$/i', $line, $m)) {
                $val = (float)$m[1];
                if ($val > 0 && $val < 100000) return $val;
            }
            // Format C: "ONU 1  1050 m" or "ONU ID: 1  Distance: 1050"
            if (preg_match('/ONU\s+(?:ID\s*:?\s*)?' . $onuNumber . '\b\D{0,30}?(\d{2,6})\s*m/i', $line, $m)) {
                $val = (float)$m[1];
                if ($val > 0 && $val < 100000) return $val;
            }
            // Format D: "Distance(m): 1050" or "Distance : 1050" or "distance=1050"
            if (preg_match('/Distance\s*[(:=]\s*(\d+)/i', $line, $m)) {
                $val = (float)$m[1];
                if ($val > 0 && $val < 100000) return $val;
            }
            // Format E: " 1  1050 m" — simple table row: onu_id spaces value m
            if (preg_match('/^\s*' . $onuNumber . '\s+(\d{2,6})\s*m?\s*$/i', $line, $m)) {
                $val = (float)$m[1];
                if ($val > 0 && $val < 100000) return $val;
            }
        }
        return null;
    }

    protected function parseBdcomOpticalOutput(string $out): array
    {
        $optical = [];
        foreach (explode("\n", $out) as $line) {
            // Match: epon0/2:1   55.6   3.3   13.1   2.0   -1.6
            if (!preg_match('/^epon\d+\/(\d+):(\d+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)/i', trim($line), $m)) continue;
            $key = "EPON0/{$m[1]}:{$m[2]}";
            $optical[$key] = [
                'port'        => "EPON0/{$m[1]}",
                'onu_number'  => (int)$m[2],
                'temperature' => (float)$m[3],
                'voltage'     => (float)$m[4],
                'tx_bias'     => (float)$m[5],
                'tx_power'    => (float)$m[6],
                'rx_power'    => (float)$m[7],
            ];
        }
        return $optical;
    }

    protected function getBdcomOpticalData(): array
    {
        $optical = [];
        for ($p = 1; $p <= 8; $p++) {
            $cmd = "show epon onu-ctc-optical-transceiver-diagnosis interface epon 0/{$p}";
            $out = $this->sendCommand($cmd, 10);
            if (str_contains($out, 'Unknown command') || str_contains($out, 'Invalid')) continue;
            $parsed = $this->parseBdcomOpticalOutput($out);
            $optical = array_merge($optical, $parsed);
            if (empty(trim($out))) break;
        }
        Log::info("BDCOM optical data collected: " . count($optical) . " entries", $optical);
        return $optical;
    }

    public function pollDashboardData(): array
    {
        $result = [
            'cpu_usage'      => null,
            'memory_usage'   => null,
            'temperature'    => null,
            'uptime_seconds' => null,
            'total_onu'      => 0,
            'online_onu'     => 0,
            'offline_onu'    => 0,
            'avg_rx_power'   => null,
            'port_traffic'   => [],
        ];
        try {
            if ($this->vendor === self::VENDOR_BDCOM) {
                $this->sendCommand('enable', 5);
                $cpuOut = $this->sendCommand('show cpu', 8);
                if (preg_match('/one second:\s*(\d+)%/', $cpuOut, $m)) {
                    $result['cpu_usage'] = (float) $m[1];
                }
                $tempOut = $this->sendCommand('show temperature', 8);
                if (preg_match('/current temperature is\s*(\d+)\s*degree/i', $tempOut, $m)) {
                    $result['temperature'] = (float) $m[1];
                }
                $memOut = $this->sendCommand('show mem-util', 6);
                if (preg_match('/current mem util is\s*(\d+)%/i', $memOut, $m)) {
                    $result['memory_usage'] = (float) $m[1];
                }
                $verOut = $this->sendCommand('show version', 10);
                if (preg_match('/uptime is\s*(\d+):(\d+):(\d+):(\d+)/i', $verOut, $m)) {
                    $result['uptime_seconds'] = ((int)$m[1]*86400) + ((int)$m[2]*3600) + ((int)$m[3]*60) + (int)$m[4];
                } elseif (preg_match('/uptime is\s*(\d+):(\d+):(\d+)/i', $verOut, $m)) {
                    $result['uptime_seconds'] = ((int)$m[1]*3600) + ((int)$m[2]*60) + (int)$m[3];
                }
                for ($p = 1; $p <= 4; $p++) {
                    $ifOut = $this->sendCommand("show interface epon 0/{$p}", 8);
                    if (str_contains($ifOut, 'Invalid') || str_contains($ifOut, 'Unknown') || empty(trim($ifOut))) continue;
                    $inRate  = 0;
                    $outRate = 0;
                    if (preg_match('/5 minutes input rate\s+(\d+)\s+bits\/sec/i', $ifOut, $mm))  $inRate  = (int)$mm[1];
                    if (preg_match('/5 minutes output rate\s+(\d+)\s+bits\/sec/i', $ifOut, $mm)) $outRate = (int)$mm[1];
                    $inBytes  = 0;
                    $outBytes = 0;
                    if (preg_match('/Received\s+\d+ packets,\s+(\d+)\s+bytes/i', $ifOut, $mm))   $inBytes  = (int)$mm[1];
                    if (preg_match('/Sent\s+\d+ packets,\s+(\d+)\s+bytes/i', $ifOut, $mm))       $outBytes = (int)$mm[1];
                    $result['port_traffic'][$p] = [
                        'port_no'     => $p,
                        'in_rate_bps' => $inRate,
                        'out_rate_bps'=> $outRate,
                        'in_octets'   => $inBytes,
                        'out_octets'  => $outBytes,
                    ];
                }
                // SNMP fallback: fetch memory_usage if Telnet did not provide it
                if ($result['memory_usage'] === null) {
                    $memRaw = $this->snmpGet('.1.3.6.1.4.1.3320.9.48.4.0');
                    if ($memRaw !== null && is_numeric($memRaw)) {
                        $result['memory_usage'] = (float)$memRaw;
                    }
                }
                // SNMP fallback: fetch temperature if Telnet did not provide it
                if ($result['temperature'] === null) {
                    $tempRaw = $this->snmpGet('.1.3.6.1.4.1.3320.9.48.5.0');
                    if ($tempRaw !== null && is_numeric($tempRaw)) {
                        $result['temperature'] = (float)$tempRaw;
                    }
                }
                // Collect optical (RX power) data
                $result['optical_data'] = $this->getBdcomOpticalData();
            }
            $onus = $this->getAllOnus();
            $result['total_onu']   = count($onus);
            $result['online_onu']  = count(array_filter($onus, fn($o) => self::isOnlineStatus($o['status'] ?? '')));
            $result['offline_onu'] = count(array_filter($onus, fn($o) => !self::isOnlineStatus($o['status'] ?? '')));

            // Calculate avg_rx_power from optical_data
            $rxPowers = array_column($result['optical_data'] ?? [], 'rx_power');
            $rxPowers = array_filter($rxPowers, fn($v) => $v !== null);
            if (count($rxPowers) > 0) {
                $result['avg_rx_power'] = round(array_sum($rxPowers) / count($rxPowers), 2);
            }
        } catch (\Exception $e) {
            Log::error("OLT pollDashboardData error: " . $e->getMessage());
        }
        return $result;
    }

    public function getBdcomOnusHttp(): array
    {
        $page = $this->httpGet('onuintfstate.asp');
        if (!$page) {
            Log::error("BDCOM: Failed to fetch onuintfstate.asp");
            return [];
        }

        $onus = [];
        preg_match_all(
            '/intfName\[(\d+)\]="([^"]+)";intfproto\[\d+\]="([^"]+)";vendorID\[\d+\]="([^"]+)";modelID\[\d+\]="([^"]+)";MACAddress\[\d+\]="([^"]+)";description\[\d+\]="([^"]*)";bindType\[\d+\]="([^"]+)";intfStatus\[\d+\]="([^"]+)";deregReason\[\d+\]="([^"]*)"/i',
            $page,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $m) {
            $intfName = $m[2];
            $port = '';
            $onuNum = '';
            if (preg_match('/EPON(\d+\/\d+):(\d+)/', $intfName, $pm)) {
                $port = 'EPON' . $pm[1];
                $onuNum = $pm[2];
            }

            $mac = str_replace('.', '', $m[6]);
            $mac = implode(':', str_split($mac, 2));

            $onus[] = [
                'interface' => $intfName,
                'port' => $port,
                'onu_number' => $onuNum,
                'status' => $m[3] === 'up' ? 'online' : 'offline',
                'vendor' => $m[4],
                'model' => $m[5],
                'mac_address' => strtoupper($mac),
                'description' => $m[7] !== 'N/A' ? $m[7] : null,
                'bind_type' => $m[8],
                'config_status' => $m[9],
                'dereg_reason' => $m[10] !== 'N/A' ? $m[10] : null,
                'serial_number' => strtoupper(str_replace(['.', ':'], '', $m[6])),
            ];
        }

        Log::info("BDCOM: Fetched " . count($onus) . " ONUs via HTTP (use Check All ONU Power to fetch optical data)");

        return $onus;
    }

    public function getBdcomOnuOpticalHttp(): array
    {
        $httpData = $this->getBdcomOnuRxPowerHttpDetail();
        if (!empty($httpData)) {
            return $httpData;
        }

        $snmpData = $this->getBdcomOnuRxPowerSnmp();
        if (!empty($snmpData)) {
            return $snmpData;
        }

        $telnetData = $this->getBdcomOnuRxPowerTelnet();
        if (!empty($telnetData)) {
            return $telnetData;
        }

        return [];
    }

    public function getBdcomOnuRxPowerHttpDetail(): array
    {
        $onus = \DB::table('onus')
            ->where('olt_device_id', $this->device->id ?? 0)
            ->whereNotNull('pon_port')
            ->whereNotNull('onu_number')
            ->select('id', 'pon_port', 'onu_number', 'status')
            ->get();

        if ($onus->isEmpty()) {
            return [];
        }

        $optics = [];
        $failCount = 0;

        foreach ($onus as $onu) {
            if ($failCount >= 5) {
                Log::warning("BDCOM: Too many consecutive failures, stopping optical fetch");
                break;
            }

            $intfName = $onu->pon_port . ':' . $onu->onu_number;
            $url = "http://{$this->host}:{$this->port}/onuintfbasicinfo.asp?intfName=" . urlencode($intfName);

            $ch = curl_init();
            $opts = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_USERPWD => "{$this->user}:{$this->pass}",
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC | CURLAUTH_DIGEST,
            ];
            if ($this->cookieFile && file_exists($this->cookieFile)) {
                $opts[CURLOPT_COOKIEJAR] = $this->cookieFile;
                $opts[CURLOPT_COOKIEFILE] = $this->cookieFile;
            }
            curl_setopt_array($ch, $opts);
            $page = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 401) {
                $this->connectHttp();
                $ch = curl_init();
                $opts[CURLOPT_COOKIEFILE] = $this->cookieFile;
                $opts[CURLOPT_COOKIEJAR] = $this->cookieFile;
                curl_setopt_array($ch, $opts);
                $page = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }

            if ($httpCode !== 200 || !$page) {
                $failCount++;
                continue;
            }

            $failCount = 0;

            $rxPower = null;
            $txPower = null;
            $temperature = null;
            $voltage = null;
            $txBias = null;

            if (preg_match('/received\s+power\s*\(D?Bm\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                $rxPower = (float) $m[1];
            }
            if (preg_match('/transmitted\s+power\s*\(D?Bm\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                $txPower = (float) $m[1];
            }
            if (preg_match('/operating\s+temperature\s*\(degree\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                $temperature = (float) $m[1];
            }
            if (preg_match('/supply\s+voltage\s*\(V\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                $voltage = (float) $m[1];
            }
            if (preg_match('/bias\s+current\s*\(mA\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                $txBias = (float) $m[1];
            }

            if ($rxPower !== null || $txPower !== null) {
                $optics[$intfName] = [
                    'rx_power' => $rxPower,
                    'tx_power' => $txPower,
                    'temperature' => $temperature,
                    'voltage' => $voltage,
                    'tx_bias' => $txBias,
                ];
            }
        }

        if (!empty($optics)) {
            Log::info("BDCOM: Got " . count($optics) . " ONU optical entries via HTTP detail pages");
        }

        return $optics;
    }

    protected function getBdcomOnuRxPowerTelnet(): array
    {
        $telnetPort = $this->device->telnet_port ?? $this->device->port ?? $this->port ?? 23;
        $fp = @fsockopen($this->host, $telnetPort, $errno, $errstr, 3);
        if (!$fp) {
            return [];
        }

        stream_set_timeout($fp, 5);
        $this->telnetRead($fp, 2);

        fwrite($fp, $this->user . "\r\n");
        usleep(500000);
        $this->telnetRead($fp, 2);

        fwrite($fp, $this->pass . "\r\n");
        usleep(500000);
        $this->telnetRead($fp, 2);

        fwrite($fp, "enable\r\n");
        usleep(300000);
        $this->telnetRead($fp, 1);

        $optics = [];

        $ports = \DB::table('onus')
            ->where('olt_device_id', $this->device->id ?? 0)
            ->select('pon_port')
            ->distinct()
            ->pluck('pon_port')
            ->toArray();

        foreach ($ports as $ponPort) {
            if (!$ponPort) continue;

            fwrite($fp, "show epon optical-transceiver-diagnosis interface {$ponPort}\r\n");
            usleep(1500000);
            stream_set_timeout($fp, 8);
            $output = $this->telnetRead($fp, 5);

            if (preg_match_all('/(\S+)\s+([-\d.]+)\s+([-\d.]+)\s+([-\d.]+)\s+([-\d.]+)\s+([-\d.]+)/m', $output, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $intf = $m[1];
                    if (stripos($intf, 'EPON') !== false || stripos($intf, 'epon') !== false) {
                        $optics[$intf] = [
                            'rx_power' => is_numeric($m[5]) ? (float) $m[5] : null,
                            'tx_power' => is_numeric($m[6]) ? (float) $m[6] : null,
                            'temperature' => is_numeric($m[2]) ? (float) $m[2] : null,
                            'voltage' => is_numeric($m[3]) ? (float) $m[3] : null,
                        ];
                    }
                }
            }
        }

        fwrite($fp, "exit\r\n");
        @fclose($fp);

        if (!empty($optics)) {
            Log::info("BDCOM: Got " . count($optics) . " ONU optical entries via Telnet");
        }

        return $optics;
    }

    private function telnetRead($fp, int $timeout = 2): string
    {
        $buf = '';
        $start = time();
        while ((time() - $start) < $timeout) {
            $c = @fread($fp, 4096);
            if ($c === false || $c === '') {
                usleep(100000);
                continue;
            }
            $buf .= $c;
            if (preg_match('/(#|>|\$)\s*$/', $buf)) break;
        }
        return $buf;
    }

    protected function httpGetLong(string $path, int $timeout = 30): ?string
    {
        $url = "http://{$this->host}:{$this->port}/{$path}";
        $ch = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERPWD => "{$this->user}:{$this->pass}",
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC | CURLAUTH_DIGEST,
        ];
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            $opts[CURLOPT_COOKIEJAR] = $this->cookieFile;
            $opts[CURLOPT_COOKIEFILE] = $this->cookieFile;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 401 && $this->cookieFile) {
            if ($this->connectHttp()) {
                return $this->httpGetWithCookie($path);
            }
        }

        return ($httpCode === 200) ? $response : null;
    }

    protected function getBdcomOnuRxPowerSnmp(): array
    {
        $snmpCommunity = $this->device->snmp_community ?? 'public';
        $snmpPort = $this->device->snmp_port ?? 161;

        $rxOid = '1.3.6.1.4.1.3320.101.10.5.1.5';
        $cmd = "snmpwalk -v2c -c " . escapeshellarg($snmpCommunity) . " -t 10 -r 1 "
             . escapeshellarg($this->host) . ":" . intval($snmpPort) . " {$rxOid} 2>/dev/null";
        $output = @shell_exec($cmd);
        if (!$output || stripos($output, 'Timeout') !== false || stripos($output, 'error') !== false) {
            return [];
        }

        $optics = [];
        $existingOnus = \DB::table('onus')
            ->where('olt_device_id', $this->device->id ?? 0)
            ->pluck('serial_number', 'id')
            ->toArray();

        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (preg_match('/\.(\d+)\.(\d+)\s*=\s*(?:INTEGER|Gauge32|STRING):\s*"?([-\d.]+)"?/i', $line, $m)) {
                $ifIndex = $m[1];
                $onuIndex = $m[2];
                $rxValue = (float) $m[3];
                if ($rxValue != 0) {
                    $rxDbm = $rxValue / 10.0;
                    $intfKey = "EPON0/{$ifIndex}:{$onuIndex}";
                    $optics[$intfKey] = [
                        'rx_power' => $rxDbm,
                        'tx_power' => null,
                    ];
                }
            }
        }

        $txOid = '1.3.6.1.4.1.3320.101.10.5.1.6';
        $cmd2 = "snmpwalk -v2c -c " . escapeshellarg($snmpCommunity) . " -t 10 -r 1 "
              . escapeshellarg($this->host) . ":" . intval($snmpPort) . " {$txOid} 2>/dev/null";
        $output2 = @shell_exec($cmd2);
        if ($output2 && stripos($output2, 'Timeout') === false) {
            $lines2 = explode("\n", trim($output2));
            foreach ($lines2 as $line) {
                if (preg_match('/\.(\d+)\.(\d+)\s*=\s*(?:INTEGER|Gauge32|STRING):\s*"?([-\d.]+)"?/i', $line, $m)) {
                    $intfKey = "EPON0/{$m[1]}:{$m[2]}";
                    if (isset($optics[$intfKey])) {
                        $optics[$intfKey]['tx_power'] = (float) $m[3] / 10.0;
                    }
                }
            }
        }

        if (!empty($optics)) {
            Log::info("BDCOM: Got " . count($optics) . " ONU optical entries via SNMP");
        }

        return $optics;
    }

    public function getBdcomDeregisteredOnusHttp(): array
    {
        $page = $this->httpGet('eponderegisteronuintflist.asp');
        if (!$page) return [];

        $onus = [];
        preg_match_all(
            '/intfName\[(\d+)\]="([^"]+)".*?MACAddress\[\d+\]="([^"]+)"/i',
            $page,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $m) {
            $mac = str_replace('.', '', $m[3]);
            $onus[] = [
                'interface' => $m[2],
                'mac_address' => strtoupper(implode(':', str_split($mac, 2))),
                'serial_number' => strtoupper(str_replace(['.', ':'], '', $m[3])),
            ];
        }

        return $onus;
    }

    public function discoverUnregisteredOnus(): array
    {
        switch ($this->vendor) {
            case self::VENDOR_HUAWEI:
                return $this->discoverHuaweiOnus();
            case self::VENDOR_ZTE:
                return $this->discoverZteOnus();
            case self::VENDOR_BDCOM:
                return $this->discoverBdcomOnus();
            case self::VENDOR_VSOL:
                return $this->discoverVsolOnus();
            case self::VENDOR_FIBERHOME:
                return $this->discoverFiberhomeOnus();
            case self::VENDOR_CDATA:
                return $this->discoverCdataOnus();
            case self::VENDOR_NETLINK:
                return $this->discoverNetlinkOnus();
            default:
                return $this->discoverGenericOnus();
        }
    }

    protected function discoverHuaweiOnus(): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $output = $this->sendCommand('display ont autofind all', 60);
        return $this->parseDiscoveredOnus($output, 'huawei');
    }

    protected function discoverZteOnus(): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('configure terminal');
        $output = $this->sendCommand('show gpon onu uncfg', 60);
        return $this->parseDiscoveredOnus($output, 'zte');
    }

    protected function discoverBdcomOnus(): array
    {
        if ($this->connectionType === self::CONN_HTTP) {
            return $this->discoverBdcomOnusHttp();
        }
        $this->sendCommand('enable');
        $output = $this->sendCommand('show epon onu-info unauth', 60);
        return $this->parseDiscoveredOnus($output, 'bdcom');
    }

    protected function discoverBdcomOnusHttp(): array
    {
        $page = $this->httpGet('onuauthlist.asp');
        if (!$page) {
            $page = $this->httpGet('unauthedonu.asp');
        }
        if (!$page) {
            Log::info("BDCOM: No unregistered ONU page found, returning empty");
            return [];
        }

        $onus = [];
        preg_match_all(
            '/intfName\[(\d+)\]="([^"]+)".*?MACAddress\[\d+\]="([^"]+)"/i',
            $page,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $m) {
            $mac = str_replace('.', '', $m[3]);
            $serial = strtoupper($mac);
            $port = '';
            if (preg_match('/EPON(\d+\/\d+):(\d+)/', $m[2], $pm)) {
                $port = 'EPON' . $pm[1];
            }
            $onus[] = [
                'port' => $port,
                'serial' => $serial,
                'vendor' => 'bdcom',
                'mac' => strtoupper(implode(':', str_split($mac, 2))),
                'interface' => $m[2],
            ];
        }

        Log::info("BDCOM: Discovered " . count($onus) . " unregistered ONUs via HTTP");
        return $onus;
    }

    protected function discoverVsolOnus(): array
    {
        $this->sendCommand('enable');
        $output = $this->sendCommand('show onu uncfg', 60);
        return $this->parseDiscoveredOnus($output, 'vsol');
    }

    protected function discoverFiberhomeOnus(): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $output = $this->sendCommand('show discovery onu all', 60);
        return $this->parseDiscoveredOnus($output, 'fiberhome');
    }

    protected function discoverCdataOnus(): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $output = $this->sendCommand('show onu unregister', 60);
        return $this->parseDiscoveredOnus($output, 'cdata');
    }

    protected function discoverNetlinkOnus(): array
    {
        $this->sendCommand('enable');
        $output = $this->sendCommand('show onu unregistered', 60);
        return $this->parseDiscoveredOnus($output, 'netlink');
    }

    protected function discoverGenericOnus(): array
    {
        $output = $this->sendCommand('show onu unregistered', 60);
        return $this->parseDiscoveredOnus($output, 'generic');
    }

    protected function parseDiscoveredOnus(string $output, string $vendor): array
    {
        $onus = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $onu = null;
            switch ($vendor) {
                case 'huawei':
                    if (preg_match('/(\d+\/\d+\/\d+)\s+(\w{4}-\w{4}-\w{4}-\w{4}|\w{16})\s+/i', $line, $m)) {
                        $onu = ['port' => $m[1], 'serial' => $m[2], 'vendor' => 'huawei'];
                    }
                    break;
                case 'zte':
                    if (preg_match('/gpon-olt_(\d+\/\d+\/\d+)\s+(\w+)\s+/i', $line, $m)) {
                        $onu = ['port' => $m[1], 'serial' => $m[2], 'vendor' => 'zte'];
                    }
                    break;
                case 'bdcom':
                    if (preg_match('/EPON(\d+\/\d+)\s+(\w{4}\.\w{4}\.\w{4})\s+/i', $line, $m)) {
                        $onu = ['port' => $m[1], 'serial' => $m[2], 'vendor' => 'bdcom'];
                    }
                    break;
                default:
                    if (preg_match('/(\d+\/\d+(?:\/\d+)?)\s+([\w.-]+)/i', $line, $m)) {
                        $serial = $m[2];
                        if (strlen($serial) >= 8 && !is_numeric($serial)) {
                            $onu = ['port' => $m[1], 'serial' => $serial, 'vendor' => $vendor];
                        }
                    }
                    break;
            }

            if ($onu) {
                $onus[] = $onu;
            }
        }

        return $onus;
    }

    public function provisionOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description = null, ?string $vlan = null): array
    {
        switch ($this->vendor) {
            case self::VENDOR_HUAWEI:
                return $this->provisionHuaweiOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
            case self::VENDOR_ZTE:
                return $this->provisionZteOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
            case self::VENDOR_BDCOM:
                return $this->provisionBdcomOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
            case self::VENDOR_VSOL:
                return $this->provisionVsolOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
            case self::VENDOR_FIBERHOME:
                return $this->provisionFiberhomeOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
            case self::VENDOR_CDATA:
                return $this->provisionCdataOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
            case self::VENDOR_NETLINK:
                return $this->provisionNetlinkOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
            default:
                return $this->provisionGenericOnu($port, $serial, $lineProfile, $serviceProfile, $description, $vlan);
        }
    }

    protected function provisionHuaweiOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description, ?string $vlan): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $this->sendCommand("interface gpon {$port}");
        $output = $this->sendCommand("ont add sn-auth {$serial} ont-lineprofile-id {$lineProfile} ont-srvprofile-id {$serviceProfile}" . ($description ? " desc \"{$description}\"" : ''));

        $onuId = null;
        if (preg_match('/ONT-ID\s*:\s*(\d+)/i', $output, $m)) {
            $onuId = $m[1];
        }

        if ($vlan && $onuId) {
            $this->sendCommand("ont port native-vlan {$onuId} eth 1 vlan {$vlan}");
        }

        $this->sendCommand('quit');
        $this->sendCommand('quit');

        $success = $onuId !== null || stripos($output, 'success') !== false;
        return [
            'success' => $success,
            'onu_id' => $onuId,
            'message' => $success ? 'ONU provisioned successfully' : 'Provision command sent',
            'output' => $output,
        ];
    }

    protected function provisionZteOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description, ?string $vlan): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('configure terminal');
        $this->sendCommand("interface gpon-olt_{$port}");
        $output = $this->sendCommand("onu {$serial} type {$serviceProfile} sn {$serial}");

        $onuId = null;
        if (preg_match('/onu\s+(\d+)/i', $output, $m)) {
            $onuId = $m[1];
        }

        if ($onuId) {
            $this->sendCommand('exit');
            $this->sendCommand("interface gpon-onu_{$port}:{$onuId}");
            if ($lineProfile) {
                $this->sendCommand("tcont 1 profile {$lineProfile}");
            }
            if ($vlan) {
                $this->sendCommand("vlan port eth_0/1 mode tag vlan {$vlan}");
            }
            if ($description) {
                $this->sendCommand("description {$description}");
            }
        }

        $this->sendCommand('exit');
        $this->sendCommand('exit');

        $success = $onuId !== null || stripos($output, 'success') !== false;
        return [
            'success' => $success,
            'onu_id' => $onuId,
            'message' => $success ? 'ONU provisioned successfully' : 'Provision command sent',
            'output' => $output,
        ];
    }

    protected function provisionBdcomOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description, ?string $vlan): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('configure terminal');
        $this->sendCommand("interface EPON{$port}");
        $output = $this->sendCommand("epon bind-onu mac {$serial} profile {$serviceProfile}");

        $onuId = null;
        if (preg_match('/onu\s*(\d+)/i', $output, $m)) {
            $onuId = $m[1];
        }

        if ($vlan && $onuId) {
            $this->sendCommand("epon onu-ctc-vlan {$onuId} mode tag {$vlan}");
        }

        $this->sendCommand('exit');
        $this->sendCommand('exit');

        $success = $onuId !== null || stripos($output, 'success') !== false;
        return [
            'success' => $success,
            'onu_id' => $onuId,
            'message' => $success ? 'ONU provisioned successfully' : 'Provision command sent',
            'output' => $output,
        ];
    }

    protected function provisionVsolOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description, ?string $vlan): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $this->sendCommand("interface gpon {$port}");
        $output = $this->sendCommand("onu add sn {$serial} profile {$serviceProfile}");

        $onuId = null;
        if (preg_match('/ONU\s*ID\s*:\s*(\d+)/i', $output, $m)) {
            $onuId = $m[1];
        }

        if ($vlan && $onuId) {
            $this->sendCommand("onu {$onuId} vlan {$vlan}");
        }
        if ($description && $onuId) {
            $this->sendCommand("onu {$onuId} description {$description}");
        }

        $this->sendCommand('exit');
        $this->sendCommand('exit');

        $success = $onuId !== null || stripos($output, 'success') !== false;
        return [
            'success' => $success,
            'onu_id' => $onuId,
            'message' => $success ? 'ONU provisioned successfully' : 'Provision command sent',
            'output' => $output,
        ];
    }

    protected function provisionFiberhomeOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description, ?string $vlan): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $this->sendCommand("interface gpon {$port}");
        $output = $this->sendCommand("ont add {$serial} line-profile {$lineProfile} srv-profile {$serviceProfile}");

        $onuId = null;
        if (preg_match('/ONT\s*ID\s*:\s*(\d+)/i', $output, $m)) {
            $onuId = $m[1];
        }

        if ($vlan && $onuId) {
            $this->sendCommand("ont vlan {$onuId} {$vlan}");
        }
        if ($description && $onuId) {
            $this->sendCommand("ont description {$onuId} {$description}");
        }

        $this->sendCommand('exit');
        $this->sendCommand('exit');

        $success = $onuId !== null || stripos($output, 'success') !== false;
        return [
            'success' => $success,
            'onu_id' => $onuId,
            'message' => $success ? 'ONU provisioned successfully' : 'Provision command sent',
            'output' => $output,
        ];
    }

    protected function provisionCdataOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description, ?string $vlan): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $this->sendCommand("interface gpon {$port}");
        $output = $this->sendCommand("onu register sn {$serial} profile {$serviceProfile} line-profile {$lineProfile}");

        $onuId = null;
        if (preg_match('/ONU\s*(\d+)/i', $output, $m)) {
            $onuId = $m[1];
        }

        if ($vlan && $onuId) {
            $this->sendCommand("onu {$onuId} vlan {$vlan}");
        }

        $this->sendCommand('exit');
        $this->sendCommand('exit');

        $success = $onuId !== null || stripos($output, 'success') !== false;
        return [
            'success' => $success,
            'onu_id' => $onuId,
            'message' => $success ? 'ONU provisioned successfully' : 'Provision command sent',
            'output' => $output,
        ];
    }

    protected function provisionNetlinkOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description, ?string $vlan): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $this->sendCommand("interface gpon {$port}");
        $output = $this->sendCommand("onu add {$serial} type {$serviceProfile} line-profile {$lineProfile}");

        $onuId = null;
        if (preg_match('/ONU\s*ID\s*:\s*(\d+)/i', $output, $m)) {
            $onuId = $m[1];
        }

        if ($vlan && $onuId) {
            $this->sendCommand("onu vlan {$onuId} {$vlan}");
        }

        $this->sendCommand('exit');
        $this->sendCommand('exit');

        $success = $onuId !== null || stripos($output, 'success') !== false;
        return [
            'success' => $success,
            'onu_id' => $onuId,
            'message' => $success ? 'ONU provisioned successfully' : 'Provision command sent',
            'output' => $output,
        ];
    }

    protected function provisionGenericOnu(string $port, string $serial, string $lineProfile, string $serviceProfile, ?string $description, ?string $vlan): array
    {
        $this->sendCommand('enable');
        $this->sendCommand('config');
        $output = $this->sendCommand("onu add {$port} sn {$serial} profile {$serviceProfile}");

        $onuId = null;
        if (preg_match('/(\d+)/', $output, $m)) {
            $onuId = $m[1];
        }

        return [
            'success' => true,
            'onu_id' => $onuId,
            'message' => 'Provision command sent',
            'output' => $output,
        ];
    }

    public function decommissionOnu(string $port, string $onuId): array
    {
        switch ($this->vendor) {
            case self::VENDOR_HUAWEI:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $this->sendCommand("interface gpon {$port}");
                $output = $this->sendCommand("ont delete {$onuId}");
                $this->sendCommand('quit');
                $this->sendCommand('quit');
                break;
            case self::VENDOR_ZTE:
                $this->sendCommand('enable');
                $this->sendCommand('configure terminal');
                $this->sendCommand("interface gpon-olt_{$port}");
                $output = $this->sendCommand("no onu {$onuId}");
                $this->sendCommand('exit');
                $this->sendCommand('exit');
                break;
            case self::VENDOR_BDCOM:
                $this->sendCommand('enable');
                $this->sendCommand('configure terminal');
                $this->sendCommand("interface EPON{$port}");
                $output = $this->sendCommand("epon unbind-onu {$onuId}");
                $this->sendCommand('exit');
                $this->sendCommand('exit');
                break;
            case self::VENDOR_VSOL:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $this->sendCommand("interface gpon {$port}");
                $output = $this->sendCommand("onu delete {$onuId}");
                $this->sendCommand('exit');
                $this->sendCommand('exit');
                break;
            case self::VENDOR_FIBERHOME:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $this->sendCommand("interface gpon {$port}");
                $output = $this->sendCommand("ont delete {$onuId}");
                $this->sendCommand('exit');
                $this->sendCommand('exit');
                break;
            case self::VENDOR_CDATA:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $this->sendCommand("interface gpon {$port}");
                $output = $this->sendCommand("onu deregister {$onuId}");
                $this->sendCommand('exit');
                $this->sendCommand('exit');
                break;
            case self::VENDOR_NETLINK:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $this->sendCommand("interface gpon {$port}");
                $output = $this->sendCommand("onu remove {$onuId}");
                $this->sendCommand('exit');
                $this->sendCommand('exit');
                break;
            default:
                $output = $this->sendCommand("onu delete {$port} {$onuId}");
                break;
        }

        $success = stripos($output, 'error') === false && stripos($output, 'fail') === false;
        return [
            'success' => $success,
            'message' => $success ? 'ONU decommissioned successfully' : 'Decommission command sent (check device)',
            'output' => $output,
        ];
    }

    public function getOnuDetailedInfo(string $port, string $onuId): array
    {
        $info = ['port' => $port, 'onu_id' => $onuId];

        switch ($this->vendor) {
            case self::VENDOR_HUAWEI:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $statusOutput = $this->sendCommand("display ont info {$port} {$onuId}");
                $opticalOutput = $this->sendCommand("display ont optical-info {$port} {$onuId}");
                $info = array_merge($info, $this->parseHuaweiOutput($statusOutput));
                $info['signal'] = $this->parseHuaweiSignal($opticalOutput);
                if (preg_match('/Temperature\(C\)\s*:\s*([-\d.]+)/', $opticalOutput, $m)) {
                    $info['temperature'] = floatval($m[1]);
                }
                if (preg_match('/OLT Rx ONT optical power\(dBm\)\s*:\s*([-\d.]+)/', $opticalOutput, $m)) {
                    $info['tx_power'] = floatval($m[1]);
                }
                if (preg_match('/Uptime\s*:\s*(.+)/i', $statusOutput, $m)) {
                    $info['uptime'] = trim($m[1]);
                }
                break;
            case self::VENDOR_ZTE:
                $this->sendCommand('enable');
                $this->sendCommand('configure terminal');
                $statusOutput = $this->sendCommand("show gpon onu detail-info gpon-onu_{$port}:{$onuId}");
                $opticalOutput = $this->sendCommand("show pon power attenuation gpon-onu_{$port}:{$onuId}");
                if (preg_match('/Status\s*:\s*(\w+)/i', $statusOutput, $m)) {
                    $info['status'] = strtolower($m[1]);
                }
                if (preg_match('/Rx\s*:\s*([-\d.]+)/i', $opticalOutput, $m)) {
                    $info['signal'] = floatval($m[1]);
                }
                if (preg_match('/Tx\s*:\s*([-\d.]+)/i', $opticalOutput, $m)) {
                    $info['tx_power'] = floatval($m[1]);
                }
                if (preg_match('/Temperature\s*:\s*([-\d.]+)/i', $opticalOutput, $m)) {
                    $info['temperature'] = floatval($m[1]);
                }
                break;
            case self::VENDOR_BDCOM:
                $this->sendCommand('enable');
                $statusOutput = $this->sendCommand("show epon onu-info EPON{$port}:{$onuId}");
                if (preg_match('/Status\s*:\s*(\w+)/i', $statusOutput, $m)) {
                    $info['status'] = strtolower($m[1]);
                }
                if (preg_match('/Rx Power\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['signal'] = floatval($m[1]);
                }
                if (preg_match('/Tx Power\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['tx_power'] = floatval($m[1]);
                }
                break;
            case self::VENDOR_VSOL:
                $this->sendCommand('enable');
                $statusOutput = $this->sendCommand("show onu info {$port} {$onuId}");
                if (preg_match('/State\s*:\s*(\w+)/i', $statusOutput, $m)) {
                    $info['status'] = strtolower($m[1]);
                }
                if (preg_match('/Rx Power\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['signal'] = floatval($m[1]);
                }
                if (preg_match('/Tx Power\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['tx_power'] = floatval($m[1]);
                }
                if (preg_match('/Temperature\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['temperature'] = floatval($m[1]);
                }
                break;
            case self::VENDOR_FIBERHOME:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $statusOutput = $this->sendCommand("show ont detail {$port} {$onuId}");
                if (preg_match('/Status\s*:\s*(\w+)/i', $statusOutput, $m)) {
                    $info['status'] = strtolower($m[1]);
                }
                if (preg_match('/Rx\s*(?:Power)?\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['signal'] = floatval($m[1]);
                }
                if (preg_match('/Temperature\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['temperature'] = floatval($m[1]);
                }
                break;
            case self::VENDOR_CDATA:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $statusOutput = $this->sendCommand("show onu detail {$port} {$onuId}");
                if (preg_match('/Status\s*:\s*(\w+)/i', $statusOutput, $m)) {
                    $info['status'] = strtolower($m[1]);
                }
                if (preg_match('/Rx\s*Power\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['signal'] = floatval($m[1]);
                }
                if (preg_match('/Temperature\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['temperature'] = floatval($m[1]);
                }
                break;
            case self::VENDOR_NETLINK:
                $this->sendCommand('enable');
                $statusOutput = $this->sendCommand("show onu detail {$port} {$onuId}");
                if (preg_match('/Status\s*:\s*(\w+)/i', $statusOutput, $m)) {
                    $info['status'] = strtolower($m[1]);
                }
                if (preg_match('/Rx\s*Power\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['signal'] = floatval($m[1]);
                }
                if (preg_match('/Temperature\s*:\s*([-\d.]+)/i', $statusOutput, $m)) {
                    $info['temperature'] = floatval($m[1]);
                }
                break;
            default:
                $statusOutput = $this->sendCommand("show onu {$port} {$onuId}");
                $info['status'] = stripos($statusOutput, 'online') !== false ? 'online' : 'offline';
                if (preg_match('/([-\d.]+)\s*dBm/i', $statusOutput, $m)) {
                    $info['signal'] = floatval($m[1]);
                }
                break;
        }

        return $info;
    }

    public function getPortUtilization(int $portNumber): array
    {
        $info = ['port' => $portNumber];

        switch ($this->vendor) {
            case self::VENDOR_HUAWEI:
                $this->sendCommand('enable');
                $this->sendCommand('config');
                $output = $this->sendCommand("display port traffic 0/0/{$portNumber}");
                if (preg_match('/Upstream\s*:\s*(\d+)/i', $output, $m)) {
                    $info['upstream_bps'] = intval($m[1]);
                }
                if (preg_match('/Downstream\s*:\s*(\d+)/i', $output, $m)) {
                    $info['downstream_bps'] = intval($m[1]);
                }
                $onuOutput = $this->sendCommand("display ont info summary 0/0/{$portNumber}");
                if (preg_match('/Total\s*:\s*(\d+)/i', $onuOutput, $m)) {
                    $info['total_onus'] = intval($m[1]);
                }
                if (preg_match('/Online\s*:\s*(\d+)/i', $onuOutput, $m)) {
                    $info['online_onus'] = intval($m[1]);
                }
                break;
            case self::VENDOR_ZTE:
                $this->sendCommand('enable');
                $output = $this->sendCommand("show interface gpon-olt_0/0/{$portNumber}");
                if (preg_match('/input rate\s*(\d+)/i', $output, $m)) {
                    $info['upstream_bps'] = intval($m[1]);
                }
                if (preg_match('/output rate\s*(\d+)/i', $output, $m)) {
                    $info['downstream_bps'] = intval($m[1]);
                }
                break;
            case self::VENDOR_BDCOM:
                $this->sendCommand('enable');
                $output = $this->sendCommand("show interface EPON0/{$portNumber}");
                if (preg_match('/input rate\s*(\d+)/i', $output, $m)) {
                    $info['upstream_bps'] = intval($m[1]);
                }
                if (preg_match('/output rate\s*(\d+)/i', $output, $m)) {
                    $info['downstream_bps'] = intval($m[1]);
                }
                break;
            default:
                $output = $this->sendCommand("show port {$portNumber} statistics");
                if (preg_match('/input.*?(\d+)/i', $output, $m)) {
                    $info['upstream_bps'] = intval($m[1]);
                }
                if (preg_match('/output.*?(\d+)/i', $output, $m)) {
                    $info['downstream_bps'] = intval($m[1]);
                }
                break;
        }

        return $info;
    }

    public function disconnect(): void
    {
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
            $this->cookieFile = null;
        }

        if ($this->connectionType === self::CONN_HTTP || $this->connectionType === self::CONN_SNMP) {
            $this->connected = false;
            return;
        }

        if ($this->connectionType === self::CONN_SSH) {
            if ($this->sshStream && is_resource($this->sshStream) && $this->sshStream !== $this->connection) {
                @fclose($this->sshStream);
            }
            if ($this->sshConnection) {
                if (is_resource($this->sshConnection) && get_resource_type($this->sshConnection) === 'process') {
                    @proc_terminate($this->sshConnection);
                }
            }
            $this->sshStream = null;
            $this->sshConnection = null;
        }

        if ($this->connection && is_resource($this->connection)) {
            @fwrite($this->connection, "exit\r\n");
            @fclose($this->connection);
        }
        $this->connection = null;
        $this->connected = false;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
