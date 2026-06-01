<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActionConfirmation extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'action_type',
        'action_description',
        'action_data',
        'status',
        'expires_at',
        'confirmed_at',
        'ip_address',
    ];

    protected $casts = [
        'action_data' => 'array',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createConfirmation(
        int $userId,
        string $actionType,
        string $description,
        array $actionData,
        int $expiresInMinutes = 10
    ): self {
        return static::create([
            'token' => Str::random(64),
            'user_id' => $userId,
            'action_type' => $actionType,
            'action_description' => $description,
            'action_data' => $actionData,
            'status' => 'pending',
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'ip_address' => request()->ip(),
        ]);
    }

    public function confirm(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        if (now()->isAfter($this->expires_at)) {
            $this->update(['status' => 'expired']);
            return false;
        }

        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return true;
    }

    public function isValid(): bool
    {
        return $this->status === 'pending' && now()->isBefore($this->expires_at);
    }
}
