<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class SmsSetting extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'provider',
        'api_key',
        'api_secret',
        'sender_id',
        'base_url',
        'is_active',
        'event_settings',
        'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'event_settings' => 'array',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];
}
