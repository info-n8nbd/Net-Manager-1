<?php

namespace App\Services\OLT;

use App\Models\OltDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BdcomTelnetPoller
{
    private OltDevice $olt;
    /** @var resource|null */
    private $socket = null;

    public function __construct(OltDevice $olt)
    {
        $this->olt = $olt;
    }

    /**
     * Fast status-only poll — no RX power (runs every minute via cron).
     */
    public function getOnuStatuses(): array
    {
        try {
            $this->connect();
            $this->login();
            $this->enterEnable();
            $this->disablePaging();
            $result = $this->fetchFromOnuInfo();
            $this->disconnect();
            return $result;
        } catch (\Exception $e) {
            Log::error("BdcomTelnetPoller [{$this->olt->name}]: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }


    /**
     * Combined poll — ONU statuses + RX power in a SINGLE telnet session.
     * Returns array of ONU data with rx_power included.
     */
    public function getOnuStatusesWithRxPowers(): array
    {
        try {
            $this->connect();
            $this->login();
            $this->enterEnable();
            $this->disablePaging();

            // Step 1: Get all ONU statuses
            $statuses = $this->fetchFromOnuInfo();

            // Step 1.5: Enhance deregistered ONUs with specific DeReg reason
            // already_applied_inactive_reason_fetch
            try {
                $inactiveReasons = $this->fetchInactiveReasons();
                foreach ($statuses as &$onuRef) {
                    if ($onuRef["status"] === "offline") {
                        $dr = $onuRef["down_reason"] ?? null;
                        if ($dr === "deregistered" || $dr === null || $dr === "") {
                            $irKey = strtoupper($onuRef["pon_port"]) . ":" . $onuRef["onu_number"];
                            if (isset($inactiveReasons[$irKey])) {
                                $onuRef["down_reason"] = $inactiveReasons[$irKey];
                            }
                        }
                    }
                }
                unset($onuRef);
            } catch (\Throwable $e) {
                Log::warning("BdcomTelnetPoller inactiveReasons step [{$this->olt->name}]: " . $e->getMessage());
            }

            // Step 2: Collect unique ports from status results
            $ports = array_unique(array_map(
                fn($o) => strtolower($o["pon_port"]),
                $statuses
            ));

            // Step 3: Get RX power per port
            $rxMap = [];
            foreach ($ports as $port) {
                try {
                    $this->send("show epon optical-transceiver-diagnosis interface {$port}\n");
                    $output = $this->readUntil("#", 15);
                    $this->parseRxOutput($output, $port, $rxMap);
                } catch (\Exception $e) {
                    Log::warning("BdcomTelnetPoller RX [{$this->olt->name}] port {$port}: " . $e->getMessage());
                }
            }

            $this->disconnect();

            // Step 4: Merge RX power into status array
            foreach ($statuses as &$onu) {
                $key = strtolower($onu["pon_port"]) . ":" . $onu["onu_number"];
                if (isset($rxMap[$key])) {
                    $onu["rx_power"]    = $rxMap[$key]["rx_power"];
                    $onu["tx_power"]    = $rxMap[$key]["tx_power"];
                    $onu["temperature"] = $rxMap[$key]["temperature"];
                    $onu["voltage"]     = $rxMap[$key]["voltage"];
                    $onu["tx_bias"]     = $rxMap[$key]["tx_bias"];
                } else {
                    $onu["rx_power"]    = null;
                    $onu["tx_power"]    = null;
                    $onu["temperature"] = null;
                    $onu["voltage"]     = null;
                    $onu["tx_bias"]     = null;
                }
            }
            unset($onu);

            Log::info("BdcomTelnetPoller [{$this->olt->name}]: combined poll — " . count($statuses) . " ONUs, " . count($ports) . " ports");
            return $statuses;

        } catch (\Exception $e) {
            Log::error("BdcomTelnetPoller combined [{$this->olt->name}]: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Full optical data poll — runs every 10 minutes.
     * Returns per-ONU: rx_power + port-level: tx_power, temperature, voltage, tx_bias.
     */
    public function getRxPowers(): array
    {
        try {
            $this->connect();
            $this->login();
            $this->enterEnable();
            $this->disablePaging();

            $ports = DB::table('onus')
                ->where('olt_device_id', $this->olt->id)
                ->whereNotNull('pon_port')
                ->distinct()
                ->pluck('pon_port')
                ->map(fn($p) => strtolower($p))
                ->toArray();

            $rxMap = [];
            foreach ($ports as $port) {
                try {
                    $this->send("show epon optical-transceiver-diagnosis interface {$port}\n");
                    $output = $this->readUntil('#', 15); // increased from 7 to 15s
                    $this->parseRxOutput($output, $port, $rxMap);
                } catch (\Exception $e) {
                    Log::warning("BdcomTelnetPoller RX [{$this->olt->name}] port {$port}: " . $e->getMessage());
                }
            }

            $this->disconnect();

            $result = [];
            foreach ($rxMap as $key => $data) {
                [$portPart, $onuNum] = explode(':', $key);
                $result[] = [
                    'pon_port'    => strtoupper($portPart),
                    'onu_number'  => (int) $onuNum,
                    'rx_power'    => $data['rx_power'],
                    'tx_power'    => $data['tx_power'],
                    'temperature' => $data['temperature'],
                    'voltage'     => $data['voltage'],
                    'tx_bias'     => $data['tx_bias'],
                ];
            }

            Log::info("BdcomTelnetPoller [{$this->olt->name}]: fetched optical data for " . count($result) . " ONUs across " . count($ports) . " ports");
            return $result;
        } catch (\Exception $e) {
            Log::error("BdcomTelnetPoller RX [{$this->olt->name}]: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Single-ONU live check for the client profile sync button.
     */
    public function getOnuLiveStatus(int $onuId): ?array
    {
        $dbOnu = DB::table('onus')->where('id', $onuId)->first();
        if (!$dbOnu || !$dbOnu->pon_port) {
            return null;
        }

        $port   = strtolower($dbOnu->pon_port);
        $onuNum = (int) $dbOnu->onu_number;

        try {
            $this->connect();
            $this->login();
            $this->enterEnable();
            $this->disablePaging();

            // Step 1: ONU status — targeted single-ONU command
            $matched = null;
            $this->send("show epon onu-info interface {$port}:{$onuNum}\n");
            $singleOut = $this->readUntil('#', 10);
            if (!str_contains($singleOut, 'Unknown command') && !str_contains($singleOut, 'Invalid')) {
                $parsed = $this->parseOnuInfo($singleOut);
                if (!empty($parsed)) {
                    $matched = $parsed[0];
                }
            }

            // Fallback: port-scoped scan (NOT global — avoids 30s+ buffer overflow)
            if (!$matched) {
                $this->send("show epon onu-info interface {$port}\n");
                $portOut = $this->readUntil('#', 20);
                $portOnus = $this->parseOnuInfo($portOut);
                foreach ($portOnus as $o) {
                    if ((int)$o['onu_number'] === $onuNum) {
                        $matched = $o;
                        break;
                    }
                }
            }

            // Step 2: Optical transceiver data (buffer is clean here)
            $rxMap = [];
            $this->send("show epon optical-transceiver-diagnosis interface {$port}\n");
            $rxOut = $this->readUntil('#', 30);
            $this->parseRxOutput($rxOut, $port, $rxMap);

            $this->disconnect();

            $rxKey = $port . ':' . $onuNum;
            $opt   = $rxMap[$rxKey] ?? [];
            return [
                'port'        => strtoupper($port),
                'onu_number'  => $onuNum,
                'status'      => $matched['status']      ?? 'offline',
                'down_reason' => $matched['down_reason'] ?? null,
                'vendor'      => $matched['vendor']      ?? $dbOnu->vendor,
                'model'       => $matched['model']       ?? $dbOnu->model,
                'rx_power'    => $opt['rx_power']    ?? null,
                'tx_power'    => $opt['tx_power']    ?? null,
                'temperature' => $opt['temperature'] ?? null,
                'voltage'     => $opt['voltage']     ?? null,
                'tx_bias'     => $opt['tx_bias']     ?? null,
                'distance'    => null,
                'source'      => 'live',
            ];
        } catch (\Exception $e) {
            Log::error("BdcomTelnetPoller liveStatus [{$this->olt->name}] ONU#{$onuId}: " . $e->getMessage());
            $this->disconnect();
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────


    /**
     * Fetch DeReg reasons for offline ONUs via "show epon inactive-onu" and fallback commands.
     * Returns map: "EPON0/X:N" => reason string (power-off, wire-down, los, deregistered)
     */
    private function fetchInactiveReasons(): array
    {
        $reasons = [];
        try {
            $cmds = [
                "show epon inactive-onu",
                "show epon onu offline-reason",
                "show epon onu-ctc-llid-deregister",
            ];
            foreach ($cmds as $cmd) {
                $this->send($cmd . "\n");
                $out = $this->readUntil("#", 10);
                if (str_contains($out, "Unknown command") || str_contains($out, "%Error") || str_contains($out, "Invalid")) {
                    continue;
                }
                foreach (explode("\n", $out) as $line) {
                    $trimmed = trim($line);
                    if (preg_match("/^(EPON\d+\/\d+):(\d+)\s+/i", $trimmed, $m)) {
                        $key = strtoupper($m[1]) . ":" . (int)$m[2];
                        $r   = $this->parseInactiveReason($trimmed);
                        if (!isset($reasons[$key]) || $reasons[$key] === "deregistered") {
                            $reasons[$key] = $r;
                        }
                    }
                }
                // Stop if we have specific reasons
                $allGeneric = count(array_filter($reasons, fn($r) => $r !== "deregistered")) === 0;
                if (!empty($reasons) && !$allGeneric) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("BdcomTelnetPoller inactiveReasons [{$this->olt->name}]: " . $e->getMessage());
        }
        return $reasons;
    }

    private function parseInactiveReason(string $line): string
    {
        if (preg_match("/DyingGasp/i", $line))                       return "power-off";
        if (preg_match("/power.?off|power.?down|dying.?gasp/i", $line)) return "power-off";
        if (preg_match("/mpcp.?down|mpcp.?timeout/i", $line))        return "wire-down";
        if (preg_match("/oam.?disc/i", $line))                        return "wire-down";
        if (preg_match("/wire.?down|link.?down|fiber/i", $line))      return "wire-down";
        if (preg_match("/\blos\b/i", $line))                        return "los";
        if (preg_match("/blocked/i", $line))                          return "blocked";
        return "deregistered";
    }

    private function connect(): void
    {
        $host = $this->olt->ip_address;
        $port = $this->olt->telnet_port ?: ($this->olt->port ?: 2323);
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 12);
        if (!$this->socket) {
            throw new \Exception("Cannot connect to {$host}:{$port} -- {$errstr} ({$errno})");
        }
        stream_set_timeout($this->socket, 8);
        usleep(300000);
        $iacBuf = @fread($this->socket, 256);
        if ($iacBuf !== false && $iacBuf !== "") {
            $reply = "";
            $len = strlen($iacBuf);
            for ($i = 0; $i < $len - 2; $i++) {
                if (ord($iacBuf[$i]) === 0xFF) {
                    $cmd = ord($iacBuf[$i + 1]);
                    $opt = ord($iacBuf[$i + 2]);
                    if ($cmd === 0xFB) { $reply .= chr(0xFF) . chr(0xFE) . chr($opt); }
                    elseif ($cmd === 0xFD) { $reply .= chr(0xFF) . chr(0xFC) . chr($opt); }
                    $i += 2;
                }
            }
            if ($reply !== "") { fwrite($this->socket, $reply); usleep(150000); }
        }
        $this->readUntil("Username:", 12);
    }


    private function login(): void
    {
        $this->send($this->olt->username . "\n");
        $this->readUntil('Password:', 5);
        $this->send($this->olt->password . "\n");
        $this->readUntil('>', 8);
    }

    private function enterEnable(): void
    {
        $this->send("enable\n");
        $this->readUntil('#', 5);
    }

    private function disablePaging(): void
    {
        $this->send("terminal length 0\n");
        $this->readUntil('#', 5);
    }

    private function fetchFromOnuInfo(): array
    {
        $this->send("show epon onu-info\n");
        $output = $this->readUntil('#', 30);
        return $this->parseOnuInfo($output);
    }

    /**
     * Parse "show epon onu-info" — each ONU spans two lines.
     */
    private function parseOnuInfo(string $output): array
    {
        $statuses = [];
        $lines    = explode("\n", $output);
        $total    = count($lines);

        for ($i = 0; $i < $total; $i++) {
            $line = rtrim($lines[$i]);
            if (!preg_match('/^(EPON\d+\/\d+):(\d+)\s+(\S+)\s+(\S+)\s+([0-9a-f.]+)/i', $line, $m)) {
                continue;
            }

            $ponPort   = strtoupper($m[1]);
            $onuNumber = (int) $m[2];
            $vendor    = $m[3];
            $model     = $m[4];
            $macRaw    = $m[5];
            $macClean  = strtoupper(str_replace('.', '', $macRaw));
            $serial    = 'BDCOM' . $macClean;
            $mac       = implode(':', str_split($macClean, 2));

            $status     = 'online';
            $downReason = null;
            if (isset($lines[$i + 1])) {
                $line2 = trim($lines[$i + 1]);
                if (preg_match('/(static|dynamic)\s+(\S+)/i', $line2, $sm)) {
                    $rawStatus = strtolower($sm[2]);
                    if (in_array($rawStatus, ['deregistered', 'lost', 'blocked', 'wait-for-dereg', 'power-off', 'power_off', 'dying-gasp', 'los', 'link-down'])) {
                        $status     = 'offline';
                        $downReason = $rawStatus;
                    }
                }
                $i++;
            }

            $statuses[] = [
                'pon_port'      => $ponPort,
                'onu_number'    => $onuNumber,
                'status'        => $status,
                'down_reason'   => $downReason,
                'vendor'        => $vendor,
                'model'         => $model,
                'serial_number' => $serial,
                'mac_address'   => $mac,
            ];
        }

        return $statuses;
    }

    /**
     * Parse "show epon optical-transceiver-diagnosis interface EPON0/X" output.
     *
     * Output format:
     *   interface    Temperature(degree)  Voltage(V)  Current(mA)  TxPower(dBm)
     *   epon0/1      41.7                 3.2         22.0         9.1
     *
     *    interface    RxPower(dBm)
     *   epon0/1:1    -19.9
     *   epon0/1:2    -19.9
     *   ...
     *
     * Port-level data (temp/voltage/tx_bias/tx_power) applies to ALL ONUs on that port.
     */
    private function parseRxOutput(string $output, string $port, array &$rxMap): void
    {
        $portTemp  = null;
        $portVolt  = null;
        $portCurr  = null;  // tx_bias (Current in mA)
        $portTxPow = null;  // tx_power (TxPower in dBm)

        // Port-level line: epon0/1  41.7  3.2  22.0  9.1
        // (Temperature  Voltage  Current  TxPower)
        $portEscaped = preg_quote($port, '/');
        if (preg_match('/^' . $portEscaped . '\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([-\d.]+)/im', $output, $pm)) {
            $portTemp  = (float) $pm[1];
            $portVolt  = (float) $pm[2];
            $portCurr  = (float) $pm[3];
            $portTxPow = (float) $pm[4];
        }

        // Per-ONU lines: epon0/1:N  -19.9
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (preg_match('/^(epon\d+\/\d+):(\d+)\s+([-\d.]+)/i', $line, $m)) {
                $key = strtolower($m[1]) . ':' . (int)$m[2];
                $rxMap[$key] = [
                    'rx_power'    => (float) $m[3],
                    'tx_power'    => $portTxPow,
                    'temperature' => $portTemp,
                    'voltage'     => $portVolt,
                    'tx_bias'     => $portCurr,
                ];
            }
        }
    }

    private function send(string $data): void
    {
        fwrite($this->socket, $data);
    }

    private function readUntil(string $needle, int $timeout = 8): string
    {
        $buf      = '';
        $deadline = time() + $timeout;
        while (time() < $deadline) {
            $chunk = fread($this->socket, 8192);
            if ($chunk !== false && $chunk !== '') {
                $buf .= $this->stripIac($chunk);
                if (str_contains($buf, $needle)) {
                    break;
                }
            }
            $meta = stream_get_meta_data($this->socket);
            // Continue reading until deadline even if individual fread times out
            usleep(80000); // 80ms
        }
        return $buf;
    }

    private function stripIac(string $data): string
    {
        return preg_replace('/\xff[\xfb-\xfe]./s', '', $data) ?? $data;
    }

    private function disconnect(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }
}
