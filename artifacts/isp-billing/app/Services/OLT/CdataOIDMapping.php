<?php

namespace App\Services\OLT;

class CdataOIDMapping extends OIDMapping
{
    const GPON_ONU_STATUS = '.1.3.6.1.4.1.34592.1.3.4.1.1.1.1.12';
    const GPON_ONU_SERIAL = '.1.3.6.1.4.1.34592.1.3.4.1.1.1.1.3';
    const GPON_ONU_RX_POWER = '.1.3.6.1.4.1.34592.1.3.4.1.1.1.1.15';
    const GPON_ONU_TX_POWER = '.1.3.6.1.4.1.34592.1.3.4.1.1.1.1.16';

    const EPON_ONU_STATUS = '.1.3.6.1.4.1.34592.1.3.3.1.1.1.1.12';
    const EPON_ONU_MAC = '.1.3.6.1.4.1.34592.1.3.3.1.1.1.1.3';
    const EPON_ONU_RX_POWER = '.1.3.6.1.4.1.34592.1.3.3.1.1.1.1.15';
    const EPON_ONU_TX_POWER = '.1.3.6.1.4.1.34592.1.3.3.1.1.1.1.16';

    const OLT_CPU = '.1.3.6.1.4.1.34592.1.3.1.1.0';
    const OLT_MEMORY = '.1.3.6.1.4.1.34592.1.3.1.2.0';
    const OLT_TEMPERATURE = '.1.3.6.1.4.1.34592.1.3.1.3.0';

    protected string $ponType;

    public function __construct(string $ponType = 'epon')
    {
        $this->ponType = strtolower($ponType);
    }

    public function getVendor(): string
    {
        return 'cdata';
    }

    public function getPonType(): string
    {
        return $this->ponType;
    }

    public function getOnuStatusOid(): ?string
    {
        return $this->ponType === 'gpon' ? self::GPON_ONU_STATUS : self::EPON_ONU_STATUS;
    }

    public function getOnuSerialOid(): ?string
    {
        return $this->ponType === 'gpon' ? self::GPON_ONU_SERIAL : null;
    }

    public function getOnuMacOid(): ?string
    {
        return $this->ponType === 'gpon' ? null : self::EPON_ONU_MAC;
    }

    public function getOnuRxPowerOid(): ?string
    {
        return $this->ponType === 'gpon' ? self::GPON_ONU_RX_POWER : self::EPON_ONU_RX_POWER;
    }

    public function getOnuTxPowerOid(): ?string
    {
        return $this->ponType === 'gpon' ? self::GPON_ONU_TX_POWER : self::EPON_ONU_TX_POWER;
    }

    public function getCpuUsageOid(): ?string
    {
        return self::OLT_CPU;
    }

    public function getMemoryUsageOid(): ?string
    {
        return self::OLT_MEMORY;
    }

    public function getTemperatureOid(): ?string
    {
        return self::OLT_TEMPERATURE;
    }

    public function formatPortName(int $portIndex): string
    {
        $type = $this->ponType === 'gpon' ? 'GPON' : 'EPON';
        return "{$type}0/{$portIndex}";
    }

    public function formatOnuInterface(int $portIndex, int $onuIndex): string
    {
        $type = $this->ponType === 'gpon' ? 'GPON' : 'EPON';
        return "{$type}0/{$portIndex}:{$onuIndex}";
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

    public function parseMacAddress($rawValue): string
    {
        $rawValue = trim($rawValue, '" ');
        $clean = strtoupper(preg_replace('/[^0-9A-F]/', '', $rawValue));
        if (strlen($clean) === 12) {
            return implode(':', str_split($clean, 2));
        }
        return strtoupper($rawValue);
    }
}
