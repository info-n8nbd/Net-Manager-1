<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RequestToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token', 'action_type', 'user_id', 'used', 'used_at', 'expires_at'
    ];

    protected $casts = [
        'used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function generate(string $actionType, ?int $userId = null, int $expiryMinutes = 30): self
    {
        return self::create([
            'token' => Str::random(64),
            'action_type' => $actionType,
            'user_id' => $userId,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    public static function validate(string $token, string $actionType): ?self
    {
        $requestToken = self::where('token', $token)
            ->where('action_type', $actionType)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        return $requestToken;
    }

    public static function consume(string $token, string $actionType): bool
    {
        $requestToken = self::validate($token, $actionType);
        
        if (!$requestToken) {
            return false;
        }

        $requestToken->update([
            'used' => true,
            'used_at' => now(),
        ]);

        return true;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return !$this->used && $this->expires_at > now();
    }

    public static function cleanup(): int
    {
        return self::where('expires_at', '<', now()->subDay())->delete();
    }
}
