<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSlaConfig extends Model
{
    protected $fillable = [
        'priority', 'response_hours', 'resolution_hours', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public static function getForPriority(string $priority): ?self
    {
        return self::where('priority', $priority)->where('is_active', true)->first();
    }
}
