<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class BillCorrection extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'correction_no', 'bill_id', 'client_id', 'correction_type', 'status',
        'original_package_amount', 'original_discount', 'original_total_amount',
        'original_due_amount', 'original_status',
        'new_package_amount', 'new_discount', 'new_total_amount', 'adjustment_amount',
        'replacement_bill_id', 'reason', 'admin_notes',
        'requested_by', 'approved_by', 'approved_at', 'ip_address'
    ];

    protected $casts = [
        'original_package_amount' => 'decimal:2',
        'original_discount' => 'decimal:2',
        'original_total_amount' => 'decimal:2',
        'original_due_amount' => 'decimal:2',
        'new_package_amount' => 'decimal:2',
        'new_discount' => 'decimal:2',
        'new_total_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($correction) {
            if (empty($correction->correction_no)) {
                $correction->correction_no = 'BC-' . date('Ymd') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
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

    public function replacementBill()
    {
        return $this->belongsTo(Bill::class, 'replacement_bill_id');
    }

    public function ledgerEntries()
    {
        return LedgerEntry::where('reference_type', 'BillCorrection')
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
