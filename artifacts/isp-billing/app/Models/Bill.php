<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Bill extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'bill_no', 'client_id', 'billing_month', 'billing_year', 'bill_date', 'due_date',
        'package_amount', 'previous_due', 'adjustment', 'discount', 'discount_reason', 'advance_used',
        'advance_applied_at', 'total_amount', 'paid_amount', 'due_amount', 'late_fee_applied', 'status', 
        'remarks', 'generated_by', 'auto_reconnect', 'is_prorated', 'connection_date', 'company_id'
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'connection_date' => 'date',
        'advance_applied_at' => 'datetime',
        'package_amount' => 'decimal:2',
        'previous_due' => 'decimal:2',
        'adjustment' => 'decimal:2',
        'discount' => 'decimal:2',
        'advance_used' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'auto_reconnect' => 'boolean',
        'is_prorated' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($bill) {
            if (empty($bill->bill_no)) {
                // Use a unique identifier to avoid race conditions
                $uniqueId = now()->format('YmdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                $bill->bill_no = 'BILL-' . $uniqueId;
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function getBillMonthAttribute()
    {
        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return ($months[$this->billing_month] ?? $this->billing_month) . '/' . $this->billing_year;
    }
}
