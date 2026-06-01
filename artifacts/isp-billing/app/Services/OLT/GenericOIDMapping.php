<?php

namespace App\Services\OLT;

class GenericOIDMapping extends OIDMapping
{
    protected string $ponType;

    public function __construct(string $ponType = 'epon')
    {
        $this->ponType = strtolower($ponType);
    }

    public function getVendor(): string
    {
        return 'generic';
    }

    public function getPonType(): string
    {
        return $this->ponType;
    }

    public function formatPortName(int $portIndex): string
    {
        $type = strtoupper($this->ponType);
        return "{$type}0/{$portIndex}";
    }

    public function formatOnuInterface(int $portIndex, int $onuIndex): string
    {
        $type = strtoupper($this->ponType);
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
