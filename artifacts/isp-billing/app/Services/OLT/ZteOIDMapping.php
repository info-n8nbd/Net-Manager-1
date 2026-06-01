<?php

namespace App\Services\OLT;

class ZteOIDMapping extends OIDMapping
{
    const GPON_ONU_STATUS = '.1.3.6.1.4.1.3902.1012.3.28.1.1.2';
    const GPON_ONU_SERIAL = '.1.3.6.1.4.1.3902.1012.3.28.1.1.5';
    const GPON_ONU_DESCRIPTION = '.1.3.6.1.4.1.3902.1012.3.28.1.1.3';
    const GPON_ONU_RX_POWER = '.1.3.6.1.4.1.3902.1012.3.28.2.1.3';
    const GPON_ONU_TX_POWER = '.1.3.6.1.4.1.3902.1012.3.28.2.1.4';
    const GPON_ONU_TEMPERATURE = '.1.3.6.1.4.1.3902.1012.3.28.2.1.12';
    const GPON_ONU_VOLTAGE = '.1.3.6.1.4.1.3902.1012.3.28.2.1.13';
    const GPON_ONU_TX_BIAS = '.1.3.6.1.4.1.3902.1012.3.28.2.1.5';
    const GPON_ONU_DISTANCE = '.1.3.6.1.4.1.3902.1012.3.28.1.1.9';
    const GPON_ONU_MAC = '.1.3.6.1.4.1.3902.1012.3.28.1.1.4';
    const GPON_ONU_LAST_DOWN = '.1.3.6.1.4.1.3902.1012.3.28.1.1.11';

    const OLT_CPU_5SEC = '.1.3.6.1.4.1.3902.1015.2.1.1.3.1.1.2';
    const OLT_MEMORY_USAGE = '.1.3.6.1.4.1.3902.1015.2.1.1.3.1.1.4';
    const OLT_TEMPERATURE = '.1.3.6.1.4.1.3902.1015.1.1.1.7.0';

    const GPON_ONU_REBOOT = '.1.3.6.1.4.1.3902.1012.3.28.1.1.5';

    public function getVendor(): string
    {
        return 'zte';
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

    public function getOnuMacOid(): ?string
    {
        return self::GPON_ONU_MAC;
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
        return self::OLT_CPU_5SEC;
    }

    public function getMemoryUsageOid(): ?string
    {
        return self::OLT_MEMORY_USAGE;
    }

    public function getTemperatureOid(): ?string
    {
        return self::OLT_TEMPERATURE;
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
        return '.1.3.6.1.4.1.3902.1012.3.28.1.1.13';
    }

    public function parseOnuIndex(string $oid): array
    {
        if (preg_match('/\.(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $oid, $m)) {
            $rack = (int)$m[1];
            $shelf = (int)$m[2];
            $slot = (int)$m[3];
            $onuId = (int)$m[4];
            $portIndex = ($rack * 10000) + ($shelf * 100) + $slot;
            return ['port_index' => $portIndex, 'onu_index' => $onuId, 'rsp' => "{$rack}/{$shelf}/{$slot}"];
        }
        if (preg_match('/\.(\d+)\.(\d+)$/', $oid, $m)) {
            return ['port_index' => (int)$m[1], 'onu_index' => (int)$m[2]];
        }
        return parent::parseOnuIndex($oid);
    }

    public function formatPortName(int $portIndex): string
    {
        if ($portIndex > 10000) {
            $rack = intdiv($portIndex, 10000);
            $rest = $portIndex % 10000;
            $shelf = intdiv($rest, 100);
            $slot = $rest % 100;
            return "gpon-olt_{$rack}/{$shelf}/{$slot}";
        }
        return "gpon-olt_0/0/{$portIndex}";
    }

    public function formatOnuInterface(int $portIndex, int $onuIndex): string
    {
        if ($portIndex > 10000) {
            $rack = intdiv($portIndex, 10000);
            $rest = $portIndex % 10000;
            $shelf = intdiv($rest, 100);
            $slot = $rest % 100;
            return "gpon-onu_{$rack}/{$shelf}/{$slot}:{$onuIndex}";
        }
        return "gpon-onu_0/0/{$portIndex}:{$onuIndex}";
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

    public function parseRxPower($rawValue): ?float
    {
        if ($rawValue === null || $rawValue === '') return null;
        $val = (int)$rawValue;
        if ($val == 65535 || $val == 0) return null;
        return $val / 100.0;
    }

    public function parseTxPower($rawValue): ?float
    {
        return $this->parseRxPower($rawValue);
    }
}
