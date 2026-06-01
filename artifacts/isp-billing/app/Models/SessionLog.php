<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionLog extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'login_at',
        'logout_at',
        'last_activity_at',
        'is_active',
        'logout_reason',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function logLogin(User $user, string $sessionId): self
    {
        $userAgent = request()->userAgent();
        $deviceType = self::detectDeviceType($userAgent);

        return static::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'login_at' => now(),
            'last_activity_at' => now(),
            'is_active' => true,
        ]);
    }

    public static function logLogout(string $sessionId, string $reason = 'user_logout'): void
    {
        static::where('session_id', $sessionId)
            ->where('is_active', true)
            ->update([
                'logout_at' => now(),
                'is_active' => false,
                'logout_reason' => $reason,
            ]);
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    protected static function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    public static function getActiveSessions(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }

    public static function terminateAllSessions(int $userId, ?string $exceptSessionId = null): void
    {
        $query = static::where('user_id', $userId)
            ->where('is_active', true);

        if ($exceptSessionId) {
            $query->where('session_id', '!=', $exceptSessionId);
        }

        $query->update([
            'logout_at' => now(),
            'is_active' => false,
            'logout_reason' => 'force_logout',
        ]);
    }
}
