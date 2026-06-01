<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientServiceCharge extends Model
{
    protected $fillable = [
        'client_id',
        'charge_type',
        'amount',
        'description',
        'payment_method',
        'payment_method_id',
        'money_receipt_no',
        'transaction_id',
        'sender_number',
        'paid_by',
        'charge_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
