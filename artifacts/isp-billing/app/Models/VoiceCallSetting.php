<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class VoiceCallSetting extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'provider', 'credentials', 'from_number', 'is_active',
        'auto_bill_reminder', 'auto_expiry_warning', 'auto_payment_confirmation',
        'reminder_days_before',
        'pbx_ws_url', 'pbx_extension', 'pbx_password', 'pbx_trunk',
    ];

    protected $hidden = [
        'credentials',
        'pbx_password',
    ];

    protected $casts = [
        'credentials'               => 'json',
        'pbx_trunk'                 => 'json',
        'is_active'                 => 'boolean',
        'auto_bill_reminder'        => 'boolean',
        'auto_expiry_warning'       => 'boolean',
        'auto_payment_confirmation' => 'boolean',
    ];

    public function getPbxWsUrlAttribute($value): string
    {
        return $value ?: 'wss://' . (config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : 'softynex.me') . '/pbx/ws';
    }

    public function getPbxExtensionAttribute($value): string
    {
        return $value ?: 'softynex_admin';
    }
}
