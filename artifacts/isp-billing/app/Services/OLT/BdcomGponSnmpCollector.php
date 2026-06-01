<?php

namespace App\Services\OLT;

use Illuminate\Support\Facades\Log;

/**
 * BDCOM GP3600 GPON OLT — SNMP-based ONU collector
 *
 * Confirmed available data via live SNMP walk on 103.85.182.17:164
 * (firmware: BDCOM GP3600-08D Version 10.3.0D Build 147850):
 *
 *  1. IF-MIB ifDescr  (.1.3.6.1.2.1.2.2.1.2)
 *       "GPON0/P:O" for each provisioned ONU (ifIndex 814+)
 *       "GPON0/P"   for each GPON port      (ifIndex 106-113)
 *
 *  2. IF-MIB ifOperStatus (.1.3.6.1.2.1.2.2.1.8)
 *       1=online, 2=offline — per ONU sub-interface ✓
 *
 *  3. IF-MIB ifInOctets  (.1.3.6.1.2.1.2.2.1.10) — per-ONU traffic IN  ✓
 *     IF-MIB ifOutOctets (.1.3.6.1.2.1.2.2.1.16) — per-ONU traffic OUT ✓
 *       Counter32 (wraps at 4 294 967 295). Online ONUs show real counters;
 *       offline ONUs show 0.
 *
 *  4. BDCOM DDM table (.1.3.6.1.4.1.3320.9.63.1.7.1) ← PORT-LEVEL only
 *       col 2 = OLT SFP tx power (0.01 dBm), col 3 = rx power (0.01 dBm)
 *       col 4 = temperature, col 5 = voltage, col 6 = bias current
 *       col 7 = SFP vendor string (e.g. "AERECH")
 *       Valid at port ifIndex (106-113); ONU sub-interfaces return -65535 (N/A).
 *
 *  5. BDCOM GP3600 GPON-specific vendor MIB (bdcomGponOnuTable):
 *       - ONU Serial Number: .1.3.6.1.4.1.3320.101.11.1.1.3  indexed by portNum.onuId
 *         Returns Hex-String e.g. "48 57 54 43 9F 93 2E 1A"
 *         → first 4 bytes ASCII + rest as hex → "HWTC9F932E1A"
 *       - Per-ONU RX Power:  .1.3.6.1.4.1.3320.101.10.5.1.5  indexed by portNum.onuId
 *         Returns value in 0.01 dBm units (integer), e.g. -2800 = -28.00 dBm
 */
class BdcomGponSnmpCollector
{
    // IF-MIB OIDs
    const IF_DESCR       = '.1.3.6.1.2.1.2.2.1.2';
    const IF_OPER_STATUS = '.1.3.6.1.2.1.2.2.1.8';
    const IF_IN_OCTETS   = '.1.3.6.1.2.1.2.2.1.10';  // per-ONU traffic IN  (Counter32)
    const IF_OUT_OCTETS  = '.1.3.6.1.2.1.2.2.1.16';  // per-ONU traffic OUT (Counter32)

    // BDCOM GP3600 DDM table (indexed by ifIndex, port-level only)
    const DDM_TX_POWER  = '.1.3.6.1.4.1.3320.9.63.1.7.1.2';  // 0.01 dBm
    const DDM_RX_POWER  = '.1.3.6.1.4.1.3320.9.63.1.7.1.3';  // 0.01 dBm
    const DDM_TEMP      = '.1.3.6.1.4.1.3320.9.63.1.7.1.4';
    const DDM_VOLTAGE   = '.1.3.6.1.4.1.3320.9.63.1.7.1.5';
    const DDM_BIAS      = '.1.3.6.1.4.1.3320.9.63.1.7.1.6';
    const DDM_VENDOR    = '.1.3.6.1.4.1.3320.9.63.1.7.1.7';

    // BDCOM GP3600 GPON vendor MIB — ONU Serial Number (indexed by portNum.onuId)
    const GPON_ONU_SERIAL_OID   = '.1.3.6.1.4.1.3320.101.11.1.1.3';

    // BDCOM GP3600 GPON vendor MIB — Per-ONU RX Power (indexed by portNum.onuId)
    const GPON_ONU_RX_POWER_OID = '.1.3.6.1.4.1.3320.101.10.5.1.5';

    protected SNMPClient $snmp;
    protected $olt;

    public function __construct($olt)
    {
        $this->olt  = $olt;
        $this->snmp = SNMPClient::fromDevice($olt);
    }

    /**
     * Collect all ONUs and return array of ONU data records.
     * Each record includes: pon_port, onu_number, interface, serial_number,
     * status, rx_power, tx_power, temperature, voltage, tx_bias, vendor,
     * in_octets, out_octets, if_index.
     */
    public function collectAll(): array
    {
        // 1. Walk ifDescr → build port map and ONU sub-interface map
        $descrMap = $this->snmp->walk(self::IF_DESCR);
        if (empty($descrMap)) {
            Log::warning("BdcomGponSnmpCollector [{$this->olt->name}]: ifDescr walk returned empty");
            return [];
        }

        // 2. Walk all needed MIB tables in parallel (each is independent)
        $statusMap  = $this->snmp->walk(self::IF_OPER_STATUS);
        $inOctets   = $this->snmp->walk(self::IF_IN_OCTETS);
        $outOctets  = $this->snmp->walk(self::IF_OUT_OCTETS);
        $ddmRx      = $this->snmp->walk(self::DDM_RX_POWER);
        $ddmTx      = $this->snmp->walk(self::DDM_TX_POWER);
        $ddmTemp    = $this->snmp->walk(self::DDM_TEMP);
        $ddmVolt    = $this->snmp->walk(self::DDM_VOLTAGE);
        $ddmBias    = $this->snmp->walk(self::DDM_BIAS);
        $ddmVend    = $this->snmp->walk(self::DDM_VENDOR);

        // 3. Walk GPON vendor MIB for per-ONU Serial Number and RX Power
        //    Index format: portNum.onuId  (e.g. .1.1 = port1/ONU1)
        $gponSerialMap  = $this->snmp->walk(self::GPON_ONU_SERIAL_OID);
        $gponRxMap      = $this->snmp->walk(self::GPON_ONU_RX_POWER_OID);

        // Build lookup: "portNum.onuId" => serial/rxPower
        $serialByPortOnu  = $this->buildPortOnuMap($gponSerialMap,  self::GPON_ONU_SERIAL_OID);
        $rxPowerByPortOnu = $this->buildPortOnuMap($gponRxMap,      self::GPON_ONU_RX_POWER_OID);

        Log::info("BdcomGponSnmpCollector [{$this->olt->name}]: GPON serial OID returned " . count($gponSerialMap) . " entries, RX power OID returned " . count($gponRxMap) . " entries");

        // 4. Separate port-level and ONU-level interfaces
        $portIfIndexMap = [];  // portNum → ifIndex
        $onuInterfaces  = [];  // ifIndex → ['port'=>int, 'onu'=>int, 'name'=>string]

        foreach ($descrMap as $oid => $name) {
            $ifIdx = (int)$this->extractSuffix($oid);
            $name  = trim($name, '"');

            if (preg_match('/^GPON0\/(\d+)$/', $name, $m)) {
                // GPON port interface: GPON0/1 … GPON0/8
                $portIfIndexMap[(int)$m[1]] = $ifIdx;
            } elseif (preg_match('/^GPON0\/(\d+):(\d+)$/', $name, $m)) {
                // ONU sub-interface: GPON0/P:O
                $onuInterfaces[$ifIdx] = [
                    'port' => (int)$m[1],
                    'onu'  => (int)$m[2],
                    'name' => $name,
                ];
            }
        }

        if (empty($onuInterfaces)) {
            Log::warning("BdcomGponSnmpCollector [{$this->olt->name}]: No GPON ONU sub-interfaces in ifDescr");
            return [];
        }

        // 5. Build port-level DDM map: portNum → optical data
        $portDdm = [];
        foreach ($portIfIndexMap as $portNum => $portIfIdx) {
            $rx     = $this->findByLastComponent($ddmRx,   $portIfIdx);
            $tx     = $this->findByLastComponent($ddmTx,   $portIfIdx);
            $temp   = $this->findByLastComponent($ddmTemp, $portIfIdx);
            $volt   = $this->findByLastComponent($ddmVolt, $portIfIdx);
            $bias   = $this->findByLastComponent($ddmBias, $portIfIdx);
            $vendor = trim($this->findByLastComponent($ddmVend, $portIfIdx) ?? '', '" ');

            $portDdm[$portNum] = [
                'rx_power'    => $this->parseDdmPower($rx),
                'tx_power'    => $this->parseDdmPower($tx),
                'temperature' => $this->parseDdmTemp($temp),
                'voltage'     => $this->parseDdmVoltage($volt),
                'bias'        => $this->parseDdmBias($bias),
                'vendor'      => $vendor ?: null,
            ];
        }

        // 6. Build ONU records
        $onus = [];
        foreach ($onuInterfaces as $ifIdx => $info) {
            $portNum = $info['port'];
            $onuNum  = $info['onu'];
            $ifName  = $info['name'];

            // Lookup key for GPON vendor MIB (portNum.onuId)
            $portOnuKey = "{$portNum}.{$onuNum}";

            // Status
            $rawStatus = $this->findByLastComponent($statusMap, $ifIdx);
            $isOnline  = ((int)$rawStatus === 1);
            $status    = $isOnline ? 'online' : 'offline';

            // Traffic counters — per-ONU (Counter32, wraps at ~4.3 GB)
            $rawIn  = $this->findByLastComponent($inOctets,  $ifIdx);
            $rawOut = $this->findByLastComponent($outOctets, $ifIdx);
            $trafficIn  = ($rawIn  !== null && $rawIn  !== '') ? (int)$rawIn  : 0;
            $trafficOut = ($rawOut !== null && $rawOut !== '') ? (int)$rawOut : 0;

            // Port-level DDM (shared for all ONUs on same GPON port)
            $ddm = $portDdm[$portNum] ?? [
                'rx_power'=>null,'tx_power'=>null,
                'temperature'=>null,'voltage'=>null,
                'bias'=>null,'vendor'=>null,
            ];

            // --- GPON Serial Number (per-ONU from vendor MIB) ---
            $rawSerial = $serialByPortOnu[$portOnuKey] ?? null;
            if ($rawSerial !== null && $rawSerial !== '' && !in_array($rawSerial, ['noSuchObject', 'noSuchInstance'])) {
                $serial = $this->parseGponSerial($rawSerial);
            } else {
                // Fallback: stable synthetic ID
                $serial = 'SYN-OLT' . $this->olt->id . '-P' . $portNum . 'O' . $onuNum;
            }

            // --- GPON Per-ONU RX Power (from vendor MIB) ---
            $rawRxPower = $rxPowerByPortOnu[$portOnuKey] ?? null;
            $onuRxPower = $this->parseGponRxPower($rawRxPower);

            $onus[] = [
                'if_index'      => $ifIdx,
                'pon_port'      => $portNum,
                'onu_number'    => $onuNum,
                'interface'     => $ifName,
                'serial_number' => $serial,
                'mac_address'   => null,
                'status'        => $status,
                'down_reason'   => $isOnline ? null : 'unknown',
                'rx_power'      => $onuRxPower,   // per-ONU RX power from GPON vendor MIB
                'tx_power'      => null,           // not available per-ONU
                'temperature'   => null,
                'voltage'       => null,
                'tx_bias'       => null,
                'vendor'        => $ddm['vendor'],
                'model'         => null,
                'distance'      => null,
                'in_octets'     => $trafficIn,
                'out_octets'    => $trafficOut,
            ];
        }

        Log::info("BdcomGponSnmpCollector [{$this->olt->name}]: Collected " . count($onus) . " ONUs — ifDescr + ifOperStatus + ifInOctets/Out + DDM + GPON Serial/RxPower");
        return $onus;
    }

    // ── GPON Vendor MIB Helpers ──────────────────────────────────────────────

    /**
     * Build a map of "portNum.onuId" => rawValue from a SNMP walk result.
     * The OID suffix is expected to end in .portNum.onuId
     * e.g. .1.3.6.1.4.1.3320.101.11.1.1.3.1.5  → key "1.5"
     */
    protected function buildPortOnuMap(array $walkResult, string $baseOid): array
    {
        $map = [];
        $baseOid = ltrim($baseOid, '.');

        foreach ($walkResult as $oid => $val) {
            $oidClean = ltrim($oid, '.');
            if (str_starts_with($oidClean, $baseOid)) {
                $suffix = ltrim(substr($oidClean, strlen($baseOid)), '.');
                if (preg_match('/^(\d+)\.(\d+)$/', $suffix, $m)) {
                    $key = $m[1] . '.' . $m[2];
                    $map[$key] = $val;
                }
            }
        }

        return $map;
    }

    /**
     * Convert BDCOM GPON ONU serial number from SNMP Hex-String to clean format.
     *
     * Input:  "48 57 54 43 9F 93 2E 1A"  (space-separated hex bytes)
     *      or raw binary string (8 bytes)
     * Output: "HWTC9F932E1A"
     *         First 4 bytes → ASCII (vendor prefix)
     *         Remaining 4 bytes → uppercase hex string
     */
    protected function parseGponSerial(?string $raw): string
    {
        if ($raw === null || trim($raw) === '') {
            return '';
        }

        $raw = trim($raw, '" ');

        // Check if it's already a clean serial (e.g. "HWTC9F932E1A")
        if (preg_match('/^[A-Z]{4}[0-9A-F]{8}$/i', $raw)) {
            return strtoupper($raw);
        }

        // Try space-separated hex bytes: "48 57 54 43 9F 93 2E 1A"
        $parts = preg_split('/[\s:]+/', trim($raw));
        $parts = array_filter($parts, fn($p) => preg_match('/^[0-9A-Fa-f]{1,2}$/', $p));
        $parts = array_values($parts);

        if (count($parts) >= 8) {
            // First 4 bytes → ASCII vendor prefix
            $vendorPrefix = '';
            for ($i = 0; $i < 4; $i++) {
                $byte = hexdec($parts[$i]);
                // Only use printable ASCII characters
                $vendorPrefix .= ($byte >= 32 && $byte <= 126) ? chr($byte) : sprintf('%02X', $byte);
            }

            // Remaining bytes → uppercase hex
            $remaining = '';
            for ($i = 4; $i < count($parts); $i++) {
                $remaining .= strtoupper(sprintf('%02X', hexdec($parts[$i])));
            }

            return $vendorPrefix . $remaining;
        }

        // Try as raw binary string (snmpget may return binary)
        if (strlen($raw) >= 8) {
            $vendorPrefix = '';
            for ($i = 0; $i < 4; $i++) {
                $byte = ord($raw[$i]);
                $vendorPrefix .= ($byte >= 32 && $byte <= 126) ? chr($byte) : sprintf('%02X', $byte);
            }

            $remaining = '';
            for ($i = 4; $i < min(strlen($raw), 8); $i++) {
                $remaining .= strtoupper(sprintf('%02X', ord($raw[$i])));
            }

            return $vendorPrefix . $remaining;
        }

        // Return as-is (cleanup whitespace)
        return strtoupper(preg_replace('/\s+/', '', $raw));
    }

    /**
     * Parse per-ONU RX power from BDCOM GPON vendor MIB.
     * OID: .1.3.6.1.4.1.3320.101.10.5.1.5
     * Returns value in 0.01 dBm units (e.g. -2800 = -28.00 dBm)
     * Invalid/stuck value check: -3097 corresponds to the stuck -30.97 dBm bug.
     */
    protected function parseGponRxPower($raw): ?float
    {
        if ($raw === null || $raw === '' || in_array($raw, ['noSuchObject', 'noSuchInstance'])) {
            return null;
        }

        $val = (int)$raw;

        // Reject known invalid/out-of-range sentinel values
        if ($val === 0 || $val === -65535 || $val === 65535) {
            return null;
        }

        // BDCOM GP3600 stuck value bug: -30.97 dBm (-3097 raw) is the default
        // fallback when the ONU is offline or the power reading is unavailable.
        // Treat this as null to avoid misleading data.
        if ($val === -3097) {
            return null;
        }

        // Reasonable optical power range: -50 dBm to +5 dBm
        // Raw units are 0.01 dBm → divide by 100
        $dbm = $val / 100.0;
        if ($dbm < -50.0 || $dbm > 5.0) {
            return null;
        }

        return round($dbm, 2);
    }

    // ── General Helpers ──────────────────────────────────────────────────────

    protected function extractSuffix(string $oid): string
    {
        return preg_match('/\.(\d+)$/', $oid, $m) ? $m[1] : '0';
    }

    protected function findByLastComponent(array $map, int $idx): ?string
    {
        foreach ($map as $oid => $val) {
            if (preg_match('/\.(\d+)$/', $oid, $m) && (int)$m[1] === $idx) {
                return $val;
            }
        }
        return null;
    }

    /** DDM optical power: 0.01 dBm units; -65535/65535 = N/A */
    protected function parseDdmPower($raw): ?float
    {
        if ($raw === null || $raw === '') return null;
        $val = (int)$raw;
        if ($val === -65535 || $val === 65535) return null;
        return round($val / 100.0, 2);
    }

    /** DDM temperature: 16-bit signed 8.8 fixed-point → divide by 256 */
    protected function parseDdmTemp($raw): ?float
    {
        if ($raw === null || $raw === '') return null;
        $val = (int)$raw;
        if ($val === -65535 || $val === 0) return null;
        return round($val / 256.0, 1);
    }

    /** DDM voltage: raw in 0.1 mV units → Volts (e.g. 33022 → 3.3022 V) */
    protected function parseDdmVoltage($raw): ?float
    {
        if ($raw === null || $raw === '') return null;
        $val = (int)$raw;
        if ($val <= 0 || $val === 65535) return null;
        return round($val / 10000.0, 4);
    }

    /** DDM bias current: 0.01 mA units → mA */
    protected function parseDdmBias($raw): ?float
    {
        if ($raw === null || $raw === '') return null;
        $val = (int)$raw;
        if ($val <= 0 || $val === 65535) return null;
        return round($val / 100.0, 2);
    }
}
