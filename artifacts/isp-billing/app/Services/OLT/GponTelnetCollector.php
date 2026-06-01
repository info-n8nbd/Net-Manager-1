<?php

namespace App\Services\OLT;

use App\Models\OltDevice;
use Illuminate\Support\Facades\Log;

class GponTelnetCollector
{
    private OltDevice $olt;
    /** @var resource|null */
    private $socket = null;
    /** @var resource|null */
    private $sshProc = null;
    /** @var resource|null */
    private $sshIn = null;
    /** @var resource|null */
    private $sshOut = null;
    /** @var resource|null */
    private $sshErr = null;
    private ?string $passFile = null;
    private bool $isSsh = false;
    private string $prompt = '#';

    public function __construct(OltDevice $olt)
    {
        $this->olt = $olt;
    }

    // ─────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────

    public function collectAll(): array
    {
        try {
            $this->connect();
            $this->login();
            $this->enterEnable();
            $this->disablePaging();

            // Step 1: Get ALL ONUs from all ports in one command
            $this->send("show gpon onu-information\n");
            $infoOutput = $this->readUntil($this->prompt, 30);
            $allOnus = GponCliParser::parseOnuInformationTable($infoOutput);

            if (empty($allOnus)) {
                Log::warning("GponTelnetCollector [{$this->olt->name}]: onu-information returned 0 ONUs — check output format");
                $this->disconnect();
                return [];
            }

            // Step 2: Collect unique port numbers from the ONU list
            $ports = [];
            foreach ($allOnus as $onu) {
                // pon_port is "GPON0/slot/port" — extract the port number
                if (preg_match('/GPON\d+\/\d+\/(\d+)/i', $onu['pon_port'], $m)) {
                    $ports[(int)$m[1]] = true;
                } elseif (preg_match('/GPON\d+\/(\d+)/i', $onu['pon_port'], $m)) {
                    $ports[(int)$m[1]] = true;
                }
            }

            // Step 3: Fetch optical (RX/TX) data per port
            $opticalMap = [];
            foreach (array_keys($ports) as $portNum) {
                try {
                    $this->send("show gpon onu-optical-transceiver-diagnosis interface GPON0/{$portNum}\n");
                    $optOut = $this->readUntil($this->prompt, 20);
                    $portOptical = GponCliParser::parseGponOpticalTransceiver($optOut);
                    $opticalMap = array_merge($opticalMap, $portOptical);
                    Log::debug("GponTelnetCollector [{$this->olt->name}]: optical port {$portNum} — " . count($portOptical) . " ONUs");
                } catch (\Exception $e) {
                    Log::warning("GponTelnetCollector [{$this->olt->name}]: optical port {$portNum} error: " . $e->getMessage());
                }
            }

            // Step 3b: Fetch distance data via "show gpon active-onu" (single command, all ports)
            $distanceMap = [];
            try {
                $this->send("show gpon active-onu\n");
                $activeOnuOut = $this->readUntil($this->prompt, 30);
                Log::info("GponTelnetCollector [{$this->olt->name}]: active-onu raw=" . substr(preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', ' ', $activeOnuOut), 0, 500));
                $distanceMap = GponCliParser::parseActiveOnuDistance($activeOnuOut);
                Log::info("GponTelnetCollector [{$this->olt->name}]: distance parsed from active-onu — " . count($distanceMap) . " ONUs");
            } catch (\Exception $e) {
                Log::warning("GponTelnetCollector [{$this->olt->name}]: active-onu distance error: " . $e->getMessage());
            }

            // Step 4: Merge optical + distance data into ONU records
            foreach ($allOnus as &$onu) {
                $portNum = null;
                if (preg_match('/GPON\d+\/\d+\/(\d+)/i', $onu['pon_port'], $m)) {
                    $portNum = (int)$m[1];
                } elseif (preg_match('/GPON\d+\/(\d+)/i', $onu['pon_port'], $m)) {
                    $portNum = (int)$m[1];
                }

                if ($portNum !== null) {
                    $key = $portNum . '/' . $onu['onu_number'];
                    if (isset($opticalMap[$key])) {
                        foreach ($opticalMap[$key] as $field => $value) {
                            if ($value !== null) {
                                $onu[$field] = $value;
                            }
                        }
                    }
                    if (isset($distanceMap[$key])) {
                        $onu['distance'] = $distanceMap[$key];
                    }
                }
            }
            unset($onu);

            $this->disconnect();

            $withRx = count(array_filter($allOnus, fn($o) => $o['rx_power'] !== null));
            Log::info("GponTelnetCollector [{$this->olt->name}]: {$withRx}/" . count($allOnus) . " ONUs have RX power");
            return $allOnus;

        } catch (\Exception $e) {
            Log::error("GponTelnetCollector [{$this->olt->name}]: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function collectPort(int $slot, int $port): array
    {
        $portStr = "gpon-onu_{$slot}/{$port}";
        $this->send("show gpon onu state {$portStr}\n");
        $stateOutput = $this->readUntil($this->prompt, 15);
        $onus = GponCliParser::parseOnuState($stateOutput, $slot, $port);
        if (empty($onus)) return [];

        $this->send("show gpon onu optical-info {$portStr}\n");
        $opticalOutput = $this->readUntil($this->prompt, 15);
        $opticalMap = GponCliParser::parseOpticalInfo($opticalOutput);

        $this->send("show mac-address gpon-onu {$slot} {$port}\n");
        $macOutput = $this->readUntil($this->prompt, 10);
        $macMap = GponCliParser::parseMacTable($macOutput);

        foreach ($onus as &$onu) {
            $id = $onu['onu_number'];
            if (isset($opticalMap[$id])) {
                $onu = array_merge($onu, array_filter($opticalMap[$id], fn($v) => $v !== null));
            }
            if (isset($macMap[$id]) && !$onu['mac_address']) {
                $onu['mac_address'] = $macMap[$id];
            }
        }
        return $onus;
    }

    public function collectOnuDetail(int $slot, int $port, int $onuId): array
    {
        try {
            $this->connect();
            $this->login();
            $this->enterEnable();
            $this->disablePaging();

            $intf = "gpon-onu_{$slot}/{$port}/{$onuId}";
            $this->send("show gpon onu detail-info {$intf}\n");
            $detail = GponCliParser::parseOnuDetailInfo($this->readUntil($this->prompt, 10));

            $this->send("show gpon onu uptime {$intf}\n");
            $upOutput = $this->readUntil($this->prompt, 8);
            if (preg_match('/Uptime\s*:\s*(.+)/i', $upOutput, $m)) {
                $detail['uptime_seconds'] = GponCliParser::parseUptime($m[1]);
            }

            $this->disconnect();
            return $detail;
        } catch (\Exception $e) {
            $this->disconnect();
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────
    // Connection management
    // ─────────────────────────────────────────────────────────

    private function connect(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGPIPE, SIG_IGN);
        }

        $host     = $this->olt->ip_address;
        $connType = $this->olt->connection_type ?? 'telnet';
        $sshPort  = (int)($this->olt->ssh_port  ?: $this->olt->telnet_port ?: 3000);
        $telPort  = (int)($this->olt->telnet_port ?: ($this->olt->port ?: 23));

        if ($connType === 'ssh' || !empty($this->olt->ssh_port)) {
            $this->isSsh = true;
            $this->connectSsh($host, $sshPort);
        } else {
            $this->isSsh = false;
            $this->connectTelnet($host, $telPort);
        }
    }

    private function connectSsh(string $host, int $port): void
    {
        $this->passFile = tempnam(sys_get_temp_dir(), 'olt_ssh_');
        file_put_contents($this->passFile, $this->olt->password);
        chmod($this->passFile, 0600);

        $cmd = implode(' ', [
            'sshpass', '-f', escapeshellarg($this->passFile),
            'ssh', '-p', $port,
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'PubkeyAuthentication=no',
            '-o', 'PasswordAuthentication=yes',
            '-o', 'ConnectTimeout=12',
            '-o', 'ServerAliveInterval=30',
            '-o', 'KexAlgorithms=+diffie-hellman-group14-sha256,diffie-hellman-group16-sha512,diffie-hellman-group1-sha1',
            '-o', 'HostKeyAlgorithms=+ssh-rsa',
            '-o', 'Ciphers=+aes256-ctr,aes128-ctr,aes256-cbc,aes128-cbc,3des-cbc',
            '-tt',
            escapeshellarg($this->olt->username . '@' . $host),
        ]);

        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $this->sshProc = proc_open($cmd, $desc, $pipes);
        if (!is_resource($this->sshProc)) {
            $this->cleanPassFile();
            throw new \Exception("SSH proc_open failed for {$host}:{$port}");
        }
        $this->sshIn  = $pipes[0];
        $this->sshOut = $pipes[1];
        $this->sshErr = $pipes[2];
        stream_set_blocking($this->sshOut, false);
        stream_set_blocking($this->sshErr, false);
        usleep(2000000);
    }

    private function connectTelnet(string $host, int $port): void
    {
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$this->socket) {
            throw new \Exception("Cannot connect to {$host}:{$port} — {$errstr} ({$errno})");
        }
        stream_set_timeout($this->socket, 30);
        usleep(400000);

        $iacBuf = @fread($this->socket, 512);
        $nonIacText = '';
        if ($iacBuf !== false && $iacBuf !== '') {
            $reply = '';
            $len = strlen($iacBuf);
            for ($i = 0; $i < $len; $i++) {
                if ($i <= $len - 3 && ord($iacBuf[$i]) === 0xFF) {
                    $cmd = ord($iacBuf[$i + 1]);
                    $opt = ord($iacBuf[$i + 2]);
                    $reply .= chr(0xFF) . ($cmd === 0xFB ? chr(0xFE) : chr(0xFC)) . chr($opt);
                    $i += 2;
                } else {
                    $nonIacText .= $iacBuf[$i];
                }
            }
            if ($reply !== '') { fwrite($this->socket, $reply); usleep(200000); }
        }

        $banner = $this->stripIac($nonIacText);
        if (!str_contains($banner, ':')) {
            $banner .= $this->readUntil(':', 12);
        }
        if (!str_contains($banner, 'Username') && !str_contains($banner, 'User') &&
            !str_contains($banner, 'login')    && !str_contains($banner, 'user')) {
            throw new \Exception("Unexpected banner (no login prompt): " . substr($banner, 0, 100));
        }
    }

    private function login(): void
    {
        if ($this->isSsh) {
            $buf = $this->readUntil(['#', '>'], 12);
            $this->prompt = str_contains($buf, '#') ? '#' : '>';
            return;
        }
        // Telnet login
        $this->send($this->olt->username . "\n");
        $this->readUntil(':', 8);          // wait for "Password:"
        $this->send($this->olt->password . "\n");
        $buf = $this->readUntil(['#', '>'], 15); // 15s — BDCOM welcome banner can be slow
        $this->prompt = str_contains($buf, '#') ? '#' : '>';
    }

    private function enterEnable(): void
    {
        if ($this->prompt === '>') {
            $this->send("enable\n");
            $buf = $this->readUntil(['#', 'Password:'], 8);
            if (str_contains($buf, 'Password:')) {
                $this->send($this->olt->password . "\n");
                $this->readUntil('#', 5);
            }
            $this->prompt = '#';
        }
    }

    private function disablePaging(): void
    {
        $this->send("terminal length 0\n");
        $this->readUntil($this->prompt, 5);
    }

    // ─────────────────────────────────────────────────────────
    // Low-level I/O (Broken Pipe protected)
    // ─────────────────────────────────────────────────────────

    private function send(string $data): void
    {
        if ($this->isSsh) {
            if (!is_resource($this->sshIn)) {
                throw new \Exception("SSH stdin pipe closed for {$this->olt->name}");
            }
            // Check if SSH process is still alive before writing
            if (is_resource($this->sshProc)) {
                $status = proc_get_status($this->sshProc);
                if (!$status["running"]) {
                    throw new \Exception("SSH process exited for {$this->olt->name} — check OLT credentials/IP");
                }
            }
            $written = @fwrite($this->sshIn, $data);
            if ($written === false || $written === 0) {
                throw new \Exception("Broken pipe: SSH stdin write failed for {$this->olt->name}");
            }
            @fflush($this->sshIn);
        } else {
            if (!is_resource($this->socket)) {
                throw new \Exception("Telnet socket closed for {$this->olt->name}");
            }
            $written = @fwrite($this->socket, $data);
            if ($written === false) {
                throw new \Exception("Broken pipe: Telnet write failed for {$this->olt->name}");
            }
        }
    }

    /** @param string|array $needles */
    private function readUntil($needles, int $timeout = 8): string
    {
        if (is_string($needles)) $needles = [$needles];
        $buf = '';
        $deadline = time() + $timeout;

        if ($this->isSsh) {
            while (time() < $deadline) {
                if (!is_resource($this->sshProc)) break;
                $status = proc_get_status($this->sshProc);
                if (!$status['running'] && $buf === '') break;
                $chunk = @fread($this->sshOut, 8192);
                if ($chunk !== false && $chunk !== '') {
                    $buf .= $chunk;
                    foreach ($needles as $needle) {
                        if (str_contains($buf, $needle)) return $buf;
                    }
                }
                @fread($this->sshErr, 1024);
                usleep(100000);
            }
            return $buf;
        }

        // Telnet path
        while (time() < $deadline) {
            if (!is_resource($this->socket)) break;
            $chunk = @fread($this->socket, 8192);
            if ($chunk !== false && $chunk !== '') {
                $buf .= $this->stripIac($chunk);
                foreach ($needles as $needle) {
                    if (str_contains($buf, $needle)) return $buf;
                }
            }
            $info = stream_get_meta_data($this->socket);
            if (!empty($info['eof'])) break;
            usleep(80000);
        }
        return $buf;
    }

    private function stripIac(string $data): string
    {
        return preg_replace('/\xff[\xfb-\xfe]./s', '', $data) ?? $data;
    }

    private function disconnect(): void
    {
        if ($this->isSsh && $this->sshProc) {
            if (is_resource($this->sshIn)) { @fwrite($this->sshIn, "quit\n"); usleep(200000); @fclose($this->sshIn); }
            if (is_resource($this->sshOut)) @fclose($this->sshOut);
            if (is_resource($this->sshErr)) @fclose($this->sshErr);
            if (is_resource($this->sshProc)) @proc_close($this->sshProc);
            $this->sshProc = $this->sshIn = $this->sshOut = $this->sshErr = null;
            $this->cleanPassFile();
        } elseif (is_resource($this->socket)) {
            @fwrite($this->socket, "quit\n");
            usleep(100000);
            @fclose($this->socket);
            $this->socket = null;
        }
    }


    // ─────────────────────────────────────────────────────────
    // Build FDB map: [MAC => ['pon_port','onu_number','serial','onu_mac','onu_id','olt']]
    // Used by OnuClientMappingService for mismatch detection on GPON OLTs
    // ─────────────────────────────────────────────────────────
    public function getFdbMap(): array
    {
        try {
            $this->connect();
            $this->login();
            $this->enterEnable();
            $this->disablePaging();

            // Load all ONUs for this OLT from DB
            $onuRecords = \Illuminate\Support\Facades\DB::table('onus')
                ->where('olt_device_id', $this->olt->id)
                ->whereNotNull('pon_port')
                ->select('id', 'pon_port', 'onu_number', 'serial_number', 'mac_address', 'client_id')
                ->get();

            // Index by "pon_port:onu_number"
            $onuByKey = [];
            foreach ($onuRecords as $row) {
                $onuByKey[$row->pon_port . ':' . $row->onu_number] = $row;
            }

            // Distinct port numbers
            $portNums = [];
            foreach ($onuRecords as $row) {
                if (preg_match('/GPON\d+\/\d+\/(\d+)/i', $row->pon_port, $m)) {
                    $portNums[(int)$m[1]] = $row->pon_port;
                } elseif (preg_match('/GPON\d+\/(\d+)/i', $row->pon_port, $m)) {
                    $portNums[(int)$m[1]] = $row->pon_port;
                }
            }

            $fdbMap = [];
            foreach ($portNums as $portNum => $ponPort) {
                try {
                    $this->send("show mac-address gpon-onu 0 {$portNum}\n");
                    $output = $this->readUntil($this->prompt, 5);
                    // Detect unsupported command on first port and abort early
                    if (str_contains($output, 'Too many parameters') || str_contains($output, 'Unknown command') || str_contains($output, 'Invalid')) {
                        \Illuminate\Support\Facades\Log::warning("GponTelnetCollector FDB [{$this->olt->name}]: mac-address command not supported, aborting.");
                        break;
                    }
                    $macMap = GponCliParser::parseMacTable($output);

                    foreach ($macMap as $onuId => $mac) {
                        if (!$mac || $mac === 'FF:FF:FF:FF:FF:FF') continue;
                        if (hexdec(substr(str_replace(':', '', $mac), 0, 2)) & 0x01) continue; // multicast

                        $key = $ponPort . ':' . $onuId;
                        $onuRec = $onuByKey[$key] ?? null;

                        $fdbMap[strtoupper($mac)] = [
                            'pon_port'   => $ponPort,
                            'onu_number' => $onuId,
                            'serial'     => $onuRec?->serial_number,
                            'onu_mac'    => $onuRec?->mac_address,
                            'onu_id'     => $onuRec?->id,
                            'olt'        => $this->olt,
                        ];
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("GponTelnetCollector FDB [{$this->olt->name}] port {$portNum}: " . $e->getMessage());
                }
            }

            $this->disconnect();
            \Illuminate\Support\Facades\Log::info("GponTelnetCollector getFdbMap [{$this->olt->name}]: " . count($fdbMap) . " MAC entries.");
            return $fdbMap;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GponTelnetCollector getFdbMap [{$this->olt->name}]: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    private function cleanPassFile(): void
    {
        if ($this->passFile && file_exists($this->passFile)) {
            unlink($this->passFile);
            $this->passFile = null;
        }
    }

    /**
     * Get OLT PON port module TX power for each active GPON port.
     * Runs "show gpon optical-transceiver interface GPON0/{port}" per port.
     * Returns: ['GPON0/1' => ['tx_power'=>2.5,'temperature'=>40.5,...], ...]
     */
    public function getPonPortTxPowers(): array
    {
        $portMetrics = [];
        try {
            $this->connect();
            $this->login();
            $this->enterEnable();
            $this->disablePaging();

            // Discover active ports from ONU list
            $this->send("show gpon onu-information\n");
            $infoOut = $this->readUntil($this->prompt, 30);
            $allOnus = GponCliParser::parseOnuInformationTable($infoOut);

            $ports = [];
            foreach ($allOnus as $onu) {
                if (preg_match('/GPON\d+\/\d+\/(\d+)/i', $onu['pon_port'], $m)) {
                    $ports[(int)$m[1]] = true;
                } elseif (preg_match('/GPON\d+\/(\d+)/i', $onu['pon_port'], $m)) {
                    $ports[(int)$m[1]] = true;
                }
            }
            // Fallback: scan ports 1-8
            if (empty($ports)) {
                for ($i = 1; $i <= 8; $i++) $ports[$i] = true;
            }

            $totalPorts = $this->olt->total_ports ?? 8;
            // Also add empty ports up to total
            for ($i = 1; $i <= $totalPorts; $i++) {
                $ports[$i] = true;
            }

            foreach (array_keys($ports) as $portNum) {
                try {
                    // Try OLT port module command first
                    $this->send("show gpon optical-transceiver interface GPON0/{$portNum}\n");
                    $out = $this->readUntil($this->prompt, 10);
                    $metrics = GponCliParser::parseGponPortTransceiver($out);

                    if ($metrics === null || $metrics['tx_power'] === null) {
                        // Fallback: derive from ONU optical diagnosis (avg of ONU TX powers)
                        $this->send("show gpon onu-optical-transceiver-diagnosis interface GPON0/{$portNum}\n");
                        $out2 = $this->readUntil($this->prompt, 15);
                        $onuOptical = GponCliParser::parseGponOpticalTransceiver($out2);
                        if (!empty($onuOptical)) {
                            $txPowers = $temps = $volts = $biases = [];
                            foreach ($onuOptical as $d) {
                                if ($d['tx_power'] !== null)    $txPowers[] = $d['tx_power'];
                                if ($d['temperature'] !== null) $temps[]    = $d['temperature'];
                                if ($d['voltage'] !== null)     $volts[]    = $d['voltage'];
                                if ($d['tx_bias'] !== null)     $biases[]   = $d['tx_bias'];
                            }
                            if (!empty($txPowers)) {
                                $metrics = [
                                    'tx_power'    => round(array_sum($txPowers) / count($txPowers), 2),
                                    'temperature' => !empty($temps)  ? round(array_sum($temps) / count($temps), 1)  : null,
                                    'voltage'     => !empty($volts)  ? round(array_sum($volts) / count($volts), 2)  : null,
                                    'tx_bias'     => !empty($biases) ? round(array_sum($biases) / count($biases), 1) : null,
                                ];
                            }
                        }
                    }

                    if ($metrics && ($metrics['tx_power'] !== null || $metrics['temperature'] !== null)) {
                        $portKey = "GPON0/{$portNum}";
                        $portMetrics[$portKey] = $metrics;
                        \Illuminate\Support\Facades\Log::info("GponTelnetCollector [{$this->olt->name}]: port {$portNum} tx={$metrics['tx_power']} temp={$metrics['temperature']}");
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("GponTelnetCollector [{$this->olt->name}]: port {$portNum} tx error: " . $e->getMessage());
                }
            }

            $this->disconnect();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GponTelnetCollector [{$this->olt->name}] getPonPortTxPowers: " . $e->getMessage());
            try { $this->disconnect(); } catch (\Exception $ex) {}
            throw $e;
        }
        return $portMetrics;
    }

}
