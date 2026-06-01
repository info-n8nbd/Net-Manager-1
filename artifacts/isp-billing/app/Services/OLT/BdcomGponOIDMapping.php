<?php

namespace App\Services\OLT;

class BdcomGponOIDMapping extends OIDMapping
{
    // BDCOM GP3600 GPON OLT - System Resources
    const GPON_CPU_USAGE       = '.1.3.6.1.4.1.3320.9.109.1.1.1.1.5.1';
    const GPON_MEMORY_USAGE    = '.1.3.6.1.4.1.3320.9.48.4.0';
    const GPON_TEMPERATURE     = '.1.3.6.1.4.1.3320.9.48.5.0';

    // BDCOM GP3600 GPON ONU Table (.1.3.6.1.4.1.3320.9.63.1.3.1.X)
    //   .1 = ONU unique index
    //   .4 = physical GPON port number (1-8)
    //   .5 = ONU ID within that port
    //   .6 = ONU operational state (1=online, 2=offline)
    const GPON_ONU_STATUS      = '.1.3.6.1.4.1.3320.9.63.1.3.1.6';
    const GPON_ONU_PORT_COL    = '.1.3.6.1.4.1.3320.9.63.1.3.1.4';
    const GPON_ONU_ID_COL      = '.1.3.6.1.4.1.3320.9.63.1.3.1.5';

    // BDCOM GP3600 GPON vendor MIB — ONU Serial Number
    // Indexed by portNum.onuId (e.g. .1.1 = port1/ONU1)
    // Returns Hex-String: "48 57 54 43 9F 93 2E 1A" → parsed to "HWTC9F932E1A"
    const GPON_ONU_SERIAL      = '.1.3.6.1.4.1.3320.101.11.1.1.3';

    // BDCOM GP3600 GPON vendor MIB — Per-ONU RX Optical Power
    // Indexed by portNum.onuId, value in 0.01 dBm units
    // -3097 = stuck/invalid value (-30.97 dBm) — filtered out in collector
    const GPON_ONU_RX_POWER    = '.1.3.6.1.4.1.3320.101.10.5.1.5';

    // Per-ONU TX Power — not available per-ONU on this firmware
    const GPON_ONU_TX_POWER    = null;

    // Port ONU count
    const GPON_PORT_ONU_COUNT  = '.1.3.6.1.4.1.3320.9.63.1.1.1.2';

    // Per-ONU reboot OID
    const GPON_REBOOT_ONU      = '.1.3.6.1.4.1.3320.9.63.1.2.1.28';

    /**
     * Port map: {suffix => port_number} populated by getOnuIndexToPortMap()
     */
    protected array $portMap = [];
    protected array $onuIdMap = [];

    public function getVendor(): string
    {
        return 'bdcom';
    }

    public function getPonType(): string
    {
        return 'gpon';
    }

    public function getOnuStatusOid(): ?string
    {
        return self::GPON_ONU_STATUS;
    }

    public function getOnuMacOid(): ?string
    {
        return null; // Not available via SNMP on this firmware
    }

    public function getOnuSerialOid(): ?string
    {
        return self::GPON_ONU_SERIAL;
    }

    public function getOnuRxPowerOid(): ?string
    {
        return self::GPON_ONU_RX_POWER;
    }

    public function getOnuTxPowerOid(): ?string
    {
        return null; // Not supported per-ONU on this firmware
    }

    public function getCpuUsageOid(): ?string
    {
        return self::GPON_CPU_USAGE;
    }

    public function getMemoryUsageOid(): ?string
    {
        return self::GPON_MEMORY_USAGE;
    }

    public function getTemperatureOid(): ?string
    {
        return self::GPON_TEMPERATURE;
    }

    public function getPonPortOnuCountOid(): ?string
    {
        return self::GPON_PORT_ONU_COUNT;
    }

    public function getRebootOid(): ?string
    {
        return self::GPON_REBOOT_ONU;
    }

    /**
     * Pre-populate port/onu-id maps from SNMP client so parseOnuIndex can use them.
     * Called by MonitoringService before pollOnuStatus.
     */
    public function getOnuIndexToPortMap(SNMPClient $client): array
    {
        $portResults  = $client->walk(self::GPON_ONU_PORT_COL);
        $onuIdResults = $client->walk(self::GPON_ONU_ID_COL);

        $this->portMap  = [];
        $this->onuIdMap = [];

        foreach ($portResults as $oid => $val) {
            if (preg_match('/\.(\d+)$/', $oid, $m)) {
                $this->portMap[$m[1]] = (int)$val;
            }
        }
        foreach ($onuIdResults as $oid => $val) {
            if (preg_match('/\.(\d+)$/', $oid, $m)) {
                $this->onuIdMap[$m[1]] = (int)$val;
            }
        }

        return $this->portMap;
    }

    public function parseOnuIndex(string $oid): array
    {
        if (preg_match('/\.(\d+)$/', $oid, $m)) {
            $suffix = $m[1];
            $portIndex = $this->portMap[$suffix]  ?? 1;
            $onuIndex  = $this->onuIdMap[$suffix] ?? (int)$suffix;
            return ['port_index' => $portIndex, 'onu_index' => $onuIndex];
        }
        return ['port_index' => 1, 'onu_index' => 0];
    }

    public function formatPortName(int $portIndex): string
    {
        return "GPON0/{$portIndex}";
    }

    public function formatOnuInterface(int $portIndex, int $onuIndex): string
    {
        return "GPON0/{$portIndex}:{$onuIndex}";
    }

    public function parseOnuStatus($rawValue): string
    {
        $val = intval($rawValue);
        return match($val) {
            1 => 'online',
            2 => 'offline',
            default => ($val >= 1 && $val <= 5) ? 'online' : 'offline',
        };
    }

    public function parseRxPower($rawValue): ?float
    {
        if ($rawValue === null || $rawValue === '' || $rawValue === 'noSuchInstance' || $rawValue === 'noSuchObject') {
            return null;
        }
        $val = (int)$rawValue;

        // Filter stuck/invalid value (-30.97 dBm bug)
        if ($val === -3097 || $val === 0 || $val === -65535 || $val === 65535) {
            return null;
        }

        // Value is in 0.01 dBm units
        $dbm = $val / 100.0;

        // Sanity range check
        if ($dbm < -50.0 || $dbm > 5.0) {
            return null;
        }

        return round($dbm, 2);
    }

    public function parseMacAddress($rawValue): string
    {
        $rawValue = trim($rawValue, '" ');
        if (preg_match('/^([0-9A-Fa-f]{2}[:\-.]){5}[0-9A-Fa-f]{2}$/', $rawValue)) {
            return strtoupper(str_replace(['-', '.'], ':', $rawValue));
        }
        $hex = bin2hex($rawValue);
        if (strlen($hex) === 12) {
            return strtoupper(implode(':', str_split($hex, 2)));
        }
        $clean = strtoupper(preg_replace('/[^0-9A-F]/', '', $rawValue));
        if (strlen($clean) === 12) {
            return implode(':', str_split($clean, 2));
        }
        return strtoupper($rawValue);
    }
}
