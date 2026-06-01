<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit breaker for network devices (MikroTik, OLT).
 * Suppresses repeated error logs when a device stays down.
 * Only logs on first failure and on recovery.
 */
class DeviceCircuitBreaker
{
    // How long (seconds) to keep the "device is down" flag before logging again
    const SUPPRESS_TTL = 1800; // 30 minutes

    /**
     * Call this when a device connection fails.
     * Returns true if we should log the error (first failure or suppression expired).
     */
    public static function onFailure(string $type, int|string $deviceId, string $deviceName, string $error): bool
    {
        $key     = "circuit_breaker:{$type}:{$deviceId}";
        $already = Cache::has($key);

        if (!$already) {
            // First failure — log it and set the suppression flag
            Log::error("[{$type}] Device down: {$deviceName} — {$error}");
            Cache::put($key, ['error' => $error, 'since' => now()->toIso8601String()], self::SUPPRESS_TTL);
            return true;
        }

        // Still down — stay silent
        return false;
    }

    /**
     * Call this when a device connects successfully.
     * Logs recovery if device was previously marked as down.
     */
    public static function onSuccess(string $type, int|string $deviceId, string $deviceName): void
    {
        $key = "circuit_breaker:{$type}:{$deviceId}";

        if (Cache::has($key)) {
            $info    = Cache::get($key, []);
            $since   = $info['since'] ?? 'unknown';
            Cache::forget($key);
            Log::info("[{$type}] Device recovered: {$deviceName} (was down since {$since})");
        }
    }

    /**
     * Check if a device is currently marked as down.
     */
    public static function isDown(string $type, int|string $deviceId): bool
    {
        return Cache::has("circuit_breaker:{$type}:{$deviceId}");
    }
}
