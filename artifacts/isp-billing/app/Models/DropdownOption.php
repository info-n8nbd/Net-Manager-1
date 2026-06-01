<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DropdownOption extends Model
{
    protected $fillable = ['group_name', 'label', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function getOptions(string $group): array
    {
        return static::where('group_name', $group)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('label')
            ->toArray();
    }

    public static function getGroupLabels(): array
    {
        return [
            'product_out_reasons' => 'Product Out Reasons',
            'fiber_out_reasons' => 'Fiber Out Reasons',
            'service_charge_types' => 'Service Charge Types',
            'leave_types' => 'Leave Types',
            'payment_reasons' => 'Payment Reasons',
            'deadline_extend_reasons' => 'Deadline Extend Reasons',
            'client_disable_reasons' => 'Client Disable Reasons',
            'client_enable_reasons' => 'Client Enable Reasons',
            'client_delete_reasons' => 'Client Delete Reasons',
        ];
    }
}
