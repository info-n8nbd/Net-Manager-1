<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinuteSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'minute_package_id', 'total_minutes', 'used_minutes',
        'remaining_minutes', 'amount_paid', 'purchased_at', 'expires_at',
        'activated_at', 'status', 'sold_by', 'reseller_id',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function package()
    {
        return $this->belongsTo(MinutePackage::class, 'minute_package_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }
}
