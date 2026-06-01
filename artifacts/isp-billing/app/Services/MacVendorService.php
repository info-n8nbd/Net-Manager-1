<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MacVendorService
{
    public static function lookup(?string $macAddress): ?string
    {
        if (!$macAddress || strlen(trim($macAddress)) < 8) {
            return null;
        }

        $mac = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $macAddress));
        if (strlen($mac) < 6) {
            return null;
        }

        $oui = substr($mac, 0, 6);

        $cacheKey = 'mac_vendor_' . $oui;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached ?: null;
        }

        try {
            $response = Http::timeout(5)->get("https://api.macvendors.com/" . rawurlencode($macAddress));

            if ($response->successful()) {
                $vendor = self::cleanVendorName(trim($response->body()));
                if ($vendor && !str_contains($vendor, 'Not Found') && !str_contains($vendor, 'errors')) {
                    Cache::put($cacheKey, $vendor, now()->addDays(30));
                    return $vendor;
                }
            }
        } catch (\Exception $e) {
            Log::debug('MAC vendor lookup failed: ' . $e->getMessage());
        }

        $vendor = self::localLookup($oui);
        if ($vendor) {
            Cache::put($cacheKey, $vendor, now()->addDays(30));
            return $vendor;
        }

        Cache::put($cacheKey, '', now()->addHours(6));
        return null;
    }

    private static function localLookup(string $oui): ?string
    {
        $commonVendors = [
            'D46E0E' => 'TP-Link', 'B0BE76' => 'TP-Link', '50C7BF' => 'TP-Link', '1062EB' => 'TP-Link',
            '54AF97' => 'TP-Link', '60E327' => 'TP-Link', '846EB1' => 'TP-Link', 'F4F26D' => 'TP-Link',
            'AC84C6' => 'TP-Link', 'A842A1' => 'TP-Link', 'E8DE27' => 'TP-Link', '30B5C2' => 'TP-Link',
            '14CC20' => 'TP-Link', '640DE4' => 'TP-Link', 'EC086B' => 'TP-Link', '74DA38' => 'TP-Link',
            '788CB5' => 'TP-Link', '18D6C7' => 'TP-Link', '5C628B' => 'TP-Link', 'B09575' => 'TP-Link',
            '2CC548' => 'MikroTik', '4C5E0C' => 'MikroTik', '6C3B6B' => 'MikroTik', 'D4CA6D' => 'MikroTik',
            '48A98A' => 'MikroTik', 'CC2DE0' => 'MikroTik', 'E48D8C' => 'MikroTik', '64D154' => 'MikroTik',
            '74DAD1' => 'MikroTik', 'B8082E' => 'MikroTik', 'B828B6' => 'MikroTik', 'C86C1E' => 'MikroTik',
            'C4AD34' => 'Tenda', '081FAF' => 'Tenda', 'C83A35' => 'Tenda', 'CC71FF' => 'Tenda',
            '00265A' => 'D-Link', '1CBDB9' => 'D-Link', '340804' => 'D-Link', '1C7EE5' => 'D-Link',
            'BCFF4D' => 'D-Link', 'C4A81D' => 'D-Link', '9094E4' => 'D-Link', '78542E' => 'D-Link',
            'B0C745' => 'D-Link', '1062E5' => 'D-Link', '1CAFF7' => 'D-Link',
            '08863B' => 'Netgear', '28C68E' => 'Netgear', '6CB0CE' => 'Netgear', 'A42B8C' => 'Netgear',
            'C43DC7' => 'Netgear', 'E46F13' => 'Netgear', '20E52A' => 'Netgear', '204E7F' => 'Netgear',
            'E091F5' => 'Netgear', 'B07FB9' => 'Netgear', '6C726D' => 'Netgear',
            '38D547' => 'ASUS', '2CFDA1' => 'ASUS', 'AC9E17' => 'ASUS', '1CB72C' => 'ASUS',
            '54A050' => 'ASUS', '60CF84' => 'ASUS', '049226' => 'ASUS', '107B44' => 'ASUS',
            'F46D04' => 'ASUS', '708BCD' => 'ASUS', 'BC5C4C' => 'ASUS',
            'B4FBE4' => 'Ubiquiti', '0418D6' => 'Ubiquiti', '24A43C' => 'Ubiquiti', '44D9E7' => 'Ubiquiti',
            'F09FC2' => 'Ubiquiti', 'FCECDA' => 'Ubiquiti', '802AA8' => 'Ubiquiti', 'AC8BA9' => 'Ubiquiti',
            '70A741' => 'Xiaomi', '64B473' => 'Xiaomi', '28E31F' => 'Xiaomi', 'D4970B' => 'Xiaomi',
            '3480B3' => 'Xiaomi', '7C1DD9' => 'Xiaomi', '8CBEBE' => 'Xiaomi', '50EC50' => 'Xiaomi',
            '000C43' => 'Ralink/MediaTek', '001217' => 'Cisco-Linksys', 'C0C1C0' => 'Cisco-Linksys',
            '20AA4B' => 'Cisco-Linksys', '584D7E' => 'Cisco-Linksys',
            '001E58' => 'D-Link', '24D3F2' => 'ZTE', '001D7E' => 'Cisco-Linksys',
            'C46E1F' => 'TP-Link', 'DCFE18' => 'TP-Link', '98254A' => 'TP-Link',
            'A0F3C1' => 'TP-Link', 'C0E42D' => 'TP-Link', '8C210A' => 'TP-Link',
            '78447E' => 'Ruijie', 'C09F42' => 'Huawei', '00E0FC' => 'Huawei', '5C7D5E' => 'Huawei',
            '48DB50' => 'Huawei', '088E4F' => 'Huawei', 'B4A9FC' => 'Huawei',
            '2816AD' => 'Huawei', '74882A' => 'Huawei', '702F35' => 'Huawei',
            '001A2A' => 'Arcadyan/DrayTek', 'FC1516' => 'DrayTek', '001DAA' => 'DrayTek',
            '0050BA' => 'D-Link', '00179A' => 'D-Link', 'BC7574' => 'Cudy',
        ];

        return $commonVendors[$oui] ?? null;
    }

    private static function cleanVendorName(string $raw): string
    {
        $brandMap = [
            'tp-link' => 'TP-Link',
            'mikrotikls' => 'MikroTik',
            'mikrotik' => 'MikroTik',
            'routerboard' => 'MikroTik',
            'tenda' => 'Tenda',
            'd-link' => 'D-Link',
            'netgear' => 'Netgear',
            'asus' => 'ASUS',
            'asustek' => 'ASUS',
            'ubiquiti' => 'Ubiquiti',
            'xiaomi' => 'Xiaomi',
            'huawei' => 'Huawei',
            'zte' => 'ZTE',
            'cisco' => 'Cisco',
            'linksys' => 'Linksys',
            'cudy' => 'Cudy',
            'totolink' => 'TOTOLINK',
            'mercury' => 'Mercury',
            'ruijie' => 'Ruijie',
            'draytek' => 'DrayTek',
            'nokia' => 'Nokia',
            'samsung' => 'Samsung',
            'apple' => 'Apple',
            'intel' => 'Intel',
            'realtek' => 'Realtek',
            'qualcomm' => 'Qualcomm',
            'mediatek' => 'MediaTek',
            'ralink' => 'Ralink/MediaTek',
            'comfast' => 'Comfast',
            'wavlink' => 'Wavlink',
            'buffalo' => 'Buffalo',
            'edimax' => 'Edimax',
            'belkin' => 'Belkin',
        ];

        $lower = strtolower($raw);
        foreach ($brandMap as $key => $clean) {
            if (str_contains($lower, $key)) {
                return $clean;
            }
        }

        $parts = preg_split('/\s+(technologies|technology|communications?|corp|co\.|ltd|inc|international|electronics|network|semi-?conductor)/i', $raw);
        if (!empty($parts[0])) {
            $name = trim($parts[0], ' .,');
            if (strlen($name) >= 2) {
                return $name;
            }
        }

        return $raw;
    }

    public static function detectFromClient($client): ?string
    {
        $mac = $client->mac_address ?? $client->session_mac ?? null;
        return self::lookup($mac);
    }
}
