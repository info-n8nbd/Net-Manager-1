<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id', 'name', 'token', 'abilities', 'last_used_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateToken(): string
    {
        return hash('sha256', Str::random(40));
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasAbility(string $ability): bool
    {
        return $this->abilities === null || in_array('*', $this->abilities) || in_array($ability, $this->abilities);
    }
}
