<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class WhatsappSetting extends Model
{
    use TenantAware;

    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'provider', 'api_url', 'api_key', 'phone_number_id',
        'business_account_id', 'access_token', 'is_active',
        'daily_limit', 'sent_today', 'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    protected $hidden = ['api_key', 'access_token'];

    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    public function canSend(): bool
    {
        return $this->is_active && $this->sent_today < $this->daily_limit;
    }

    public function incrementSentCount(): void
    {
        $this->increment('sent_today');
    }
}
