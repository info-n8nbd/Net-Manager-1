<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerBillingConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id', 'billing_mode', 'daily_rate_per_client', 'per_minute_rate',
        'last_billed_date', 'auto_deduct_wallet', 'suspend_on_zero_balance',
    ];

    protected $casts = [
        'daily_rate_per_client' => 'decimal:2',
        'per_minute_rate' => 'decimal:4',
        'last_billed_date' => 'date',
        'auto_deduct_wallet' => 'boolean',
        'suspend_on_zero_balance' => 'boolean',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }
}
