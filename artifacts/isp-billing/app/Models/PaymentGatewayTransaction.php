<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayTransaction extends Model
{
    protected $fillable = [
        'transaction_id', 'gateway', 'client_id', 'bill_id',
        'amount', 'status', 'gateway_response', 'verified_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'verified_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
