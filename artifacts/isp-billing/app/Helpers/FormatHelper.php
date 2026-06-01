<?php

namespace App\Helpers;

class FormatHelper
{
    public static function bytes($bytes, $decimals = 2): string
    {
        $bytes = (float) ($bytes ?? 0);
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = floor(log($bytes, 1024));
        if ($i < 0) $i = 0;
        if ($i >= count($units)) $i = count($units) - 1;

        return round($bytes / pow(1024, $i), $decimals) . ' ' . $units[$i];
    }
}
