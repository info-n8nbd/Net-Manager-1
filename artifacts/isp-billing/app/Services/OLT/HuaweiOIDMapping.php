<?php

namespace App\Services\OLT;

class HuaweiOIDMapping extends OIDMapping
{
    const GPON_ONU_STATUS = '.1.3.6.1.4.1.2011.6.128.1.1.2.46.1.15';
    const GPON_ONU_SERIAL = '.1.3.6.1.4.1.2011.6.128.1.1.2.43.1.3';
    const GPON_ONU_DESCRIPTION = '.1.3.6.1.4.1.2011.6.128.1.1.2.43.1.9';
    const GPON_ONU_RX_POWER = '.1.3.6.1.4.1.2011.6.128.1.1.2.51.1.4';
    const GPON_ONU_TX_POWER = '.1.3.6.1.4.1.2011.6.128.1.1.2.51.1.5';
    const GPON_ONU_TEMPERATURE = '.1.3.6.1.4.1.2011.6.128.1.1.2.51.1.1';
    const GPON_ONU_VOLTAGE = '.1.3.6.1.4.1.2011.6.128.1.1.2.51.1.2';
    const GPON_ONU_TX_BIAS = '.1.3.6.1.4.1.2011.6.128.1.1.2.51.1.6';
    const GPON_ONU_DISTANCE = '.1.3.6.1.4.1.2011.6.128.1.1.2.46.1.20';
    const GPON_ONU_LAST_DOWN_CAUSE = '.1.3.6.1.4.1.2011.6.128.1.1.2.46.1.24';
    const GPON_ONU_LAST_DOWN_TIME = '.1.3.6.1.4.1.2011.6.128.1.1.2.46.1.23';

    const OLT_CPU_5MIN = '.1.3.6.1.4.1.2011.6.3.4.1.4.3.0';
    const OLT_MEMORY_USAGE = '.1.3.6.1.4.1.2011.6.3.5.1.1.2.0';
    const OLT_TEMPERATURE = '.1.3.6.1.4.1.2011.6.3.4.1.2.0';

    const PON_PORT_STATUS = '.1.3.6.1.4.1.2011.6.128.1.1.2.22.1.1';
    const GPON_ONU_REBOOT = '.1.3.6.1.4.1.2011.6.128.1.1.2.46.1.4';

    public function getVendor(): string
    {
        return 'huawei';
    }

    public function getPonType(): string
    {
        return 'gpon';
    }

    public function getOnuStatusOid(): ?string
    {
        return self::GPON_ONU_STATUS;
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
        return self::GPON_ONU_TX_POWER;
    }

    public function getOnuDistanceOid(): ?string
    {
        return self::GPON_ONU_DISTANCE;
    }

    public function getCpuUsageOid(): ?string
    {
        return self::OLT_CPU_5MIN;
    }

    public function getMemoryUsageOid(): ?string
    {
        return self::OLT_MEMORY_USAGE;
    }

    public function getTemperatureOid(): ?string
    {
        return self::OLT_TEMPERATURE;
    }

    public function getPonPortStatusOid(): ?string
    {
        return self::PON_PORT_STATUS;
    }

    public function getRebootOid(): ?string
    {
        return self::GPON_ONU_REBOOT;
    }

    public function getOnuTemperatureOid(): ?string
    {
        return self::GPON_ONU_TEMPERATURE;
    }

    public function getOnuUptimeOid(): ?string
    {
        return self::GPON_ONU_LAST_DOWN_TIME;
    }

    public function parseOnuIndex(string $oid): array
    {
        if (preg_match('/\.(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $oid, $m)) {
            $frame = (int)$m[1];
            $slot = (int)$m[2];
            $port = (int)$m[3];
            $onuId = (int)$m[4];
            $portIndex = ($frame * 10000) + ($slot * 100) + $port;
            return ['port_index' => $portIndex, 'onu_index' => $onuId, 'fsp' => "{$frame}/{$slot}/{$port}"];
        }
        return parent::parseOnuIndex($oid);
    }

    public function formatPortName(int $portIndex): string
    {
        $frame = intdiv($portIndex, 10000);
        $rest = $portIndex % 10000;
        $slot = intdiv($rest, 100);
        $port = $rest % 100;
        return "GPON {$frame}/{$slot}/{$port}";
    }

    public function formatOnuInterface(int $portIndex, int $onuIndex): string
    {
        $frame = intdiv($portIndex, 10000);
        $rest = $portIndex % 10000;
        $slot = intdiv($rest, 100);
        $port = $rest % 100;
        return "{$frame}/{$slot}/{$port}:{$onuIndex}";
    }

    public function parseOnuStatus($rawValue): string
    {
        $val = intval($rawValue);
        return match($val) {
            1 => 'online',
            2 => 'offline',
            default => 'unknown',
        };
    }

    public function parseRxPower($rawValue): ?float
    {
        if ($rawValue === null || $rawValue === '' || $rawValue === 'noSuchInstance') return null;
        $val = (int)$rawValue;
        if ($val == 2147483647 || $val == -2147483648 || $val == 0) return null;
        return $val / 100.0;
    }

    public function parseTxPower($rawValue): ?float
    {
        return $this->parseRxPower($rawValue);
    }
}
