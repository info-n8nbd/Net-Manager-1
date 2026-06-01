<?php

namespace App\Services\OLT;

class OIDMapping
{
    const SYSTEM_DESCR = '.1.3.6.1.2.1.1.1.0';
    const SYSTEM_UPTIME = '.1.3.6.1.2.1.1.3.0';
    const SYSTEM_NAME = '.1.3.6.1.2.1.1.5.0';
    const SYSTEM_LOCATION = '.1.3.6.1.2.1.1.6.0';
    const SYSTEM_CONTACT = '.1.3.6.1.2.1.1.4.0';

    const IF_DESCR = '.1.3.6.1.2.1.2.2.1.2';
    const IF_TYPE = '.1.3.6.1.2.1.2.2.1.3';
    const IF_SPEED = '.1.3.6.1.2.1.2.2.1.5';
    const IF_ADMIN_STATUS = '.1.3.6.1.2.1.2.2.1.7';
    const IF_OPER_STATUS = '.1.3.6.1.2.1.2.2.1.8';
    const IF_IN_OCTETS = '.1.3.6.1.2.1.2.2.1.10';
    const IF_OUT_OCTETS = '.1.3.6.1.2.1.2.2.1.16';
    const IF_IN_ERRORS = '.1.3.6.1.2.1.2.2.1.14';
    const IF_OUT_ERRORS = '.1.3.6.1.2.1.2.2.1.20';

    const IF_HC_IN_OCTETS = '.1.3.6.1.2.1.31.1.1.1.6';
    const IF_HC_OUT_OCTETS = '.1.3.6.1.2.1.31.1.1.1.10';
    const IF_NAME = '.1.3.6.1.2.1.31.1.1.1.1';
    const IF_ALIAS = '.1.3.6.1.2.1.31.1.1.1.18';

    public function getVendor(): string
    {
        return 'generic';
    }

    public function getPonType(): string
    {
        return 'epon';
    }

    public function getOnuListOid(): ?string
    {
        return null;
    }

    public function getOnuStatusOid(): ?string
    {
        return null;
    }

    public function getOnuMacOid(): ?string
    {
        return null;
    }

    public function getOnuSerialOid(): ?string
    {
        return null;
    }

    public function getOnuRxPowerOid(): ?string
    {
        return null;
    }

    public function getOnuTxPowerOid(): ?string
    {
        return null;
    }

    public function getOnuDistanceOid(): ?string
    {
        return null;
    }

    public function getCpuUsageOid(): ?string
    {
        return null;
    }

    public function getMemoryUsageOid(): ?string
    {
        return null;
    }

    public function getTemperatureOid(): ?string
    {
        return null;
    }

    public function getFirmwareOid(): ?string
    {
        return null;
    }

    public function getPonPortStatusOid(): ?string
    {
        return null;
    }

    public function getPonPortOnuCountOid(): ?string
    {
        return null;
    }

    public function getRebootOid(): ?string
    {
        return null;
    }

    public function getOnuTemperatureOid(): ?string
    {
        return null;
    }

    public function getOnuUptimeOid(): ?string
    {
        return null;
    }

    public function getOnuInOctetsOid(): ?string
    {
        return null;
    }

    public function getOnuOutOctetsOid(): ?string
    {
        return null;
    }

    public function parseOnuIndex(string $oid): array
    {
        if (preg_match('/\.(\d+)\.(\d+)$/', $oid, $m)) {
            return ['port_index' => (int)$m[1], 'onu_index' => (int)$m[2]];
        }
        return ['port_index' => 0, 'onu_index' => 0];
    }

    public function formatPortName(int $portIndex): string
    {
        return "Port {$portIndex}";
    }

    public function formatOnuInterface(int $portIndex, int $onuIndex): string
    {
        return "{$portIndex}:{$onuIndex}";
    }

    public function parseRxPower($rawValue): ?float
    {
        if ($rawValue === null || $rawValue === '' || $rawValue === 'noSuchInstance' || $rawValue === 'noSuchObject') {
            return null;
        }
        $val = (float)$rawValue;
        if (abs($val) > 100) {
            return $val / 10.0;
        }
        return $val;
    }

    public function parseTxPower($rawValue): ?float
    {
        return $this->parseRxPower($rawValue);
    }

    public function parseUptime($rawValue): ?int
    {
        if ($rawValue === null) return null;

        if (is_numeric($rawValue)) {
            return intval($rawValue) / 100;
        }

        if (preg_match('/\((\d+)\)/', $rawValue, $m)) {
            return intval($m[1]) / 100;
        }

        $seconds = 0;
        if (preg_match('/(\d+)\s*day/i', $rawValue, $m)) $seconds += intval($m[1]) * 86400;
        if (preg_match('/(\d+):(\d+):(\d+)/', $rawValue, $m)) {
            $seconds += intval($m[1]) * 3600 + intval($m[2]) * 60 + intval($m[3]);
        }
        return $seconds > 0 ? $seconds : null;
    }


    /**
     * Auto-detect whether an OLT is GPON or EPON.
     * Strategy:
     *   1. Read sysDescr and check for GPON/EPON keywords (fast, 1 SNMP GET)
     *   2. Vendor-specific probe: walk a GPON-only OID — if data comes back → GPON
     *   3. Default to 'epon' if undecided
     *
     * @param  SNMPClient  $client   Already-configured SNMP client for the device
     * @param  string      $vendor   Normalised vendor key (e.g. 'bdcom', 'huawei')
     * @return string  'gpon' or 'epon'
     */
    public static function detectPonType(SNMPClient $client, string $vendor = ''): string
    {
        $vendor = strtolower(trim($vendor));

        // --- 1. Parse sysDescr ---
        try {
            $sysDescr = strtolower((string)($client->get(self::SYSTEM_DESCR) ?? ''));
        } catch (\Exception $e) {
            $sysDescr = '';
        }

        // GPON keywords in sysDescr
        $gponKeywords = ['gpon', 'gp3600', 'gp5600', 'gp7600', 'gp3', 'gp5', 'gp6', 'gp7', 'ma5800', 'ma5801', 'ma5803', 'ma5868', 'c320', 'c300', 'an6'];
        foreach ($gponKeywords as $kw) {
            if (str_contains($sysDescr, $kw)) {
                return 'gpon';
            }
        }

        // EPON keywords in sysDescr
        $eponKeywords = ['epon', 'p3608', 'p3616', 'p3624', 'ep3', 'ma5608', 'ma5683', 'ma5626', 'c220', 'c200'];
        foreach ($eponKeywords as $kw) {
            if (str_contains($sysDescr, $kw)) {
                return 'epon';
            }
        }

        // --- 2. Vendor-specific probe OIDs ---
        if ($vendor === 'bdcom') {
            // BDCOM GPON ONU provisioning table (only exists on GPON OLTs)
            try {
                $gponProbe = $client->walk('.1.3.6.1.4.1.3320.9.63.1.2.1.1');
                if (!empty($gponProbe)) {
                    return 'gpon';
                }
            } catch (\Exception $e) {}

            // BDCOM EPON ONU MAC table (only exists on EPON OLTs)
            try {
                $eponProbe = $client->walk('.1.3.6.1.4.1.3320.101.10.1.1.3');
                if (!empty($eponProbe)) {
                    return 'epon';
                }
            } catch (\Exception $e) {}
        }

        if ($vendor === 'huawei') {
            // Huawei GPON port table
            try {
                $probe = $client->walk('.1.3.6.1.4.1.2011.6.128.1.1.2.46.1.1');
                if (!empty($probe)) return 'gpon';
            } catch (\Exception $e) {}
        }

        if ($vendor === 'zte') {
            // ZTE GPON ONU table
            try {
                $probe = $client->walk('.1.3.6.1.4.1.3902.1082.500.10.2.1.1');
                if (!empty($probe)) return 'gpon';
            } catch (\Exception $e) {}
        }

        // --- 3. Default ---
        return 'epon';
    }

    public static function forVendor(string $vendor, string $ponType = 'epon'): self
    {
        $vendor = strtolower($vendor);
        $ponType = strtolower($ponType);
        return match(true) {
            $vendor === 'bdcom' && $ponType === 'gpon' => new BdcomGponOIDMapping(),
            $vendor === 'bdcom' => new BdcomOIDMapping(),
            $vendor === 'vsol' => new VsolOIDMapping($ponType),
            $vendor === 'huawei' => new HuaweiOIDMapping(),
            $vendor === 'zte' => new ZteOIDMapping(),
            $vendor === 'fiberhome' => new FiberhomeOIDMapping($ponType),
            $vendor === 'cdata' => new CdataOIDMapping($ponType),
            $vendor === 'netlink' => new NetlinkOIDMapping($ponType),
            default => new GenericOIDMapping($ponType),
        };
    }
}
