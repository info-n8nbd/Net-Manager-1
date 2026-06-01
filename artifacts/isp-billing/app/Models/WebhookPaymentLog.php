<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookPaymentLog extends Model
{
    protected $fillable = [
        'gateway', 'trx_id', 'sender_number', 'reference', 'amount',
        'client_id', 'payment_id', 'company_id', 'status',
        'failure_reason', 'raw_payload', 'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
