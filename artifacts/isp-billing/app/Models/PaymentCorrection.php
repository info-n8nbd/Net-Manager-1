<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_no', 'payment_id', 'client_id', 'correction_type', 'status',
        'original_amount', 'original_bill_id', 'original_transaction_id',
        'new_amount', 'new_bill_id', 'reversal_amount',
        'duplicate_of_payment_id', 'duplicate_reason',
        'reason', 'admin_notes',
        'requested_by', 'approved_by', 'approved_at', 'ip_address'
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'reversal_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($correction) {
            if (empty($correction->correction_no)) {
                $correction->correction_no = 'PC-' . date('Ymd') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function duplicateOfPayment()
    {
        return $this->belongsTo(Payment::class, 'duplicate_of_payment_id');
    }

    public function originalBill()
    {
        return $this->belongsTo(Bill::class, 'original_bill_id');
    }

    public function newBill()
    {
        return $this->belongsTo(Bill::class, 'new_bill_id');
    }

    public function ledgerEntries()
    {
        return LedgerEntry::where('reference_type', 'PaymentCorrection')
            ->where('reference_id', $this->id)
            ->get();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
