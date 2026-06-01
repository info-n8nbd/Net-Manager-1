<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ResellerClientRecharge extends Model
{
    use TenantAware;

    protected $fillable = [
        'reseller_id', 'client_id', 'bill_month', 'bill_year',
        'package_id', 'package_name', 'package_price', 'recharge_amount',
        'daily_rate', 'credited_amount',
        'start_date', 'end_date', 'days', 'status',
        'transaction_type', 'recharged_by', 'remarks', 'company_id',
    ];

    protected $casts = [
        'package_price' => 'decimal:2',
        'recharge_amount' => 'decimal:2',
        'daily_rate' => 'decimal:4',
        'credited_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function rechargedByUser()
    {
        return $this->belongsTo(User::class, 'recharged_by');
    }
}
