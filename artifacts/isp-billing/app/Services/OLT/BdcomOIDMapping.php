<?php

namespace App\Services\OLT;

class BdcomOIDMapping extends OIDMapping
{
    const EPON_ONU_INDEX = '.1.3.6.1.4.1.3320.101.6.1.1.1';
    const EPON_ONU_STATUS = '.1.3.6.1.4.1.3320.101.6.1.1.26';
    const EPON_ONU_MAC = '.1.3.6.1.4.1.3320.101.10.1.1.3';
    const EPON_ONU_DESCRIPTION = '.1.3.6.1.4.1.3320.101.6.1.1.16';

    const EPON_ONU_RX_POWER = '.1.3.6.1.4.1.3320.101.10.5.1.5';
    const EPON_ONU_TX_POWER = '.1.3.6.1.4.1.3320.101.10.5.1.6';
    const EPON_ONU_TEMPERATURE = '.1.3.6.1.4.1.3320.101.10.5.1.3';
    const EPON_ONU_VOLTAGE = '.1.3.6.1.4.1.3320.101.10.5.1.4';
    const EPON_ONU_TX_BIAS = '.1.3.6.1.4.1.3320.101.10.5.1.2';
    const EPON_ONU_DISTANCE = '.1.3.6.1.4.1.3320.101.10.1.1.27';
    const EPON_ONU_TX_BYTES   = '.1.3.6.1.4.1.3320.101.10.1.1.13'; // upstream (ONU to OLT)
    const EPON_ONU_RX_BYTES   = '.1.3.6.1.4.1.3320.101.10.1.1.14'; // downstream (OLT to ONU)
    const EPON_ONU_HW_VERSION = '.1.3.6.1.4.1.3320.101.10.1.1.4';
    const EPON_ONU_SW_VERSION = '.1.3.6.1.4.1.3320.101.10.1.1.5';
    const EPON_ONU_FW_VERSION = '.1.3.6.1.4.1.3320.101.10.1.1.6';

    const EPON_PORT_ONU_COUNT = '.1.3.6.1.4.1.3320.101.12.1.1.5';

    const EPON_OLT_CPU = '.1.3.6.1.4.1.3320.9.109.1.1.1.1.5.1';
    const EPON_OLT_MEMORY = '.1.3.6.1.4.1.3320.9.48.4.0';
    const EPON_OLT_TEMPERATURE = '.1.3.6.1.4.1.3320.9.48.5.0';

    const EPON_DEREG_ONU = '.1.3.6.1.4.1.3320.101.11.1.1.1';
    const EPON_DEREG_ONU_MAC = '.1.3.6.1.4.1.3320.101.11.1.1.3';

    const EPON_REBOOT_ONU = '.1.3.6.1.4.1.3320.101.6.1.1.22';

    public function getVendor(): string
    {
        return 'bdcom';
    }

    public function getPonType(): string
    {
        return 'epon';
    }

    public function getOnuListOid(): ?string
    {
        return self::EPON_ONU_INDEX;
    }

    public function getOnuStatusOid(): ?string
    {
        return self::EPON_ONU_STATUS;
    }

    public function getOnuMacOid(): ?string
    {
        return self::EPON_ONU_MAC;
    }

    public function getOnuRxPowerOid(): ?string
    {
        return self::EPON_ONU_RX_POWER;
    }

    public function getOnuTxPowerOid(): ?string
    {
        return self::EPON_ONU_TX_POWER;
    }

    public function getOnuDistanceOid(): ?string
    {
        return self::EPON_ONU_DISTANCE;
    }

    public function getOnuInOctetsOid(): ?string
    {
        return self::EPON_ONU_RX_BYTES; // downstream bytes = down (client receives)
    }

    public function getOnuOutOctetsOid(): ?string
    {
        return self::EPON_ONU_TX_BYTES; // upstream bytes = up (client sends)
    }

    public function getCpuUsageOid(): ?string
    {
        return self::EPON_OLT_CPU;
    }

    public function getMemoryUsageOid(): ?string
    {
        return self::EPON_OLT_MEMORY;
    }

    public function getTemperatureOid(): ?string
    {
        return self::EPON_OLT_TEMPERATURE;
    }

    public function getPonPortOnuCountOid(): ?string
    {
        return self::EPON_PORT_ONU_COUNT;
    }

    public function getRebootOid(): ?string
    {
        return self::EPON_REBOOT_ONU;
    }

    public function getOnuTemperatureOid(): ?string
    {
        return self::EPON_ONU_TEMPERATURE;
    }

    public function parseOnuIndex(string $oid): array
    {
        // BDCOM optical table uses single-integer index (e.g. .34)
        // Decode: portIdx=(idx-1)/32 (0-based), onuIdx=(idx-1)%32+1 (1-based)
        if (preg_match('/\.(\d+)$/', $oid, $m)) {
            $idx     = (int)$m[1];
            $portIdx = (int)(($idx - 1) / 32);
            $onuIdx  = ($idx - 1) % 32 + 1;
            return ['port_index' => $portIdx, 'onu_index' => $onuIdx];
        }
        return ['port_index' => 0, 'onu_index' => 0];
    }

    public function formatPortName(int $portIndex): string
    {
        return "EPON0/{$portIndex}";
    }

    public function formatOnuInterface(int $portIndex, int $onuIndex): string
    {
        return "EPON0/{$portIndex}:{$onuIndex}";
    }

    public function parseOnuStatus($rawValue): string
    {
        $val = intval($rawValue);
        return match($val) {
            1 => 'online',
            2 => 'offline',
            3 => 'deregistered',
            default => 'unknown',
        };
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

    /**
     * BDCOM EPON SNMP always reports optical power in units of 0.1 dBm.
     * Always divide raw integer by 10 to get dBm value.
     *
     * The base OIDMapping class uses abs(val)>100 heuristic which fails for
     * values in (-100, 100): e.g. raw -93 = -9.3 dBm, raw 21 = 2.1 dBm.
     */
    public function parseRxPower($rawValue): ?float
    {
        $noData = ['noSuchInstance', 'noSuchObject', 'endOfMibView'];
        if ($rawValue === null || $rawValue === '' || in_array($rawValue, $noData, true)) {
            return null;
        }
        $val = (float) $rawValue;
        if ($val === 0.0) {
            return null;
        }
        // Filter SNMP sentinel values: BDCOM returns -65535 when ONU has no optical data
        // Valid optical power range is -50 to +5 dBm
        if ($val <= -65000 || $val >= 500) {
            return null;
        }
        $dbm = round($val / 10.0, 2);
        if ($dbm < -50.0 || $dbm > 5.0) {
            return null;
        }
        return $dbm;
    }

    public function parseTxPower($rawValue): ?float
    {
        return $this->parseRxPower($rawValue);
    }
}
