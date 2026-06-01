<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Payment extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'payment_no', 'client_id', 'bill_id', 'amount', 'payment_method_id',
        'payment_date', 'transaction_id', 'money_receipt_no', 'remarks', 'collected_by',
        'status', 'source', 'approved_by', 'approved_at',
        'submitted_to_admin', 'submitted_at', 'submission_id', 'notes', 'payment_reason', 'company_id', 'reason'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($payment) {
            if (empty($payment->payment_no)) {
                // Use a unique identifier to avoid race conditions
                $uniqueId = now()->format('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $payment->payment_no = 'PAY-' . $uniqueId;
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
