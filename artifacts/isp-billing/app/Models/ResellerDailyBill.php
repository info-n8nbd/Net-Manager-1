<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerDailyBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id', 'billing_date', 'active_clients_count', 'total_minutes',
        'amount', 'wallet_balance_before', 'wallet_balance_after', 'status', 'remarks',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'amount' => 'decimal:2',
        'wallet_balance_before' => 'decimal:2',
        'wallet_balance_after' => 'decimal:2',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }
}
