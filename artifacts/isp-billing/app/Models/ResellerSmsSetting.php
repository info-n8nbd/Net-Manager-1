<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerSmsSetting extends Model
{
    protected $fillable = [
        'reseller_id', 'is_enabled', 'provider', 'api_key', 'sender_id', 'api_url',
        'custom_api_config', 'event_settings', 'templates',
    ];

    protected $casts = [
        'is_enabled'       => 'boolean',
        'custom_api_config' => 'array',
        'event_settings'   => 'array',
        'templates'        => 'array',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public static function forReseller(int $resellerId): self
    {
        return static::firstOrCreate(
            ['reseller_id' => $resellerId],
            [
                'is_enabled' => false,
                'provider'   => 'custom',
                'event_settings' => [
                    'line_activated'      => true,
                    'bill_generated'      => true,
                    'payment_received'    => true,
                    'due_reminder'        => true,
                    'line_deactivated'    => true,
                ],
                'templates' => [
                    'line_activated'   => 'Dear {name}, your internet connection has been activated. Package: {package}. - {company}',
                    'bill_generated'   => 'Dear {name}, your bill for {month} is {amount} BDT. Payment due by: {due_date}. - {company}',
                    'payment_received' => 'Dear {name}, payment of {amount} BDT received. Remaining due: {due} BDT. Thank you. - {company}',
                    'due_reminder'     => 'Dear {name}, your outstanding bill is {amount} BDT. Please pay as soon as possible. - {company}',
                    'line_deactivated' => 'Dear {name}, your internet connection has been suspended. Please contact us. - {company}',
                ],
            ]
        );
    }

    public function getTemplate(string $event): string
    {
        $templates = $this->templates ?? [];
        return $templates[$event] ?? '';
    }

    public function isEventEnabled(string $event): bool
    {
        $events = $this->event_settings ?? [];
        return (bool) ($events[$event] ?? true);
    }

    public function getCustomApiConfig(): array
    {
        return $this->custom_api_config ?? [];
    }
}
