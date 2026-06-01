<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ResellerBill extends Model
{
    use TenantAware;
    protected $fillable = [
        'bill_no',
        'reseller_id',
        'bill_month',
        'bill_year',
        'total_clients',
        'active_clients',
        'total_recharge',
        'total_paid',
        'due_amount',
        'commission_rate',
        'commission_amount',
        'discount_amount',
        'opening_balance',
        'closing_balance',
        'status',
        'company_id',
        'bandwidth_breakdown',
    ];

    protected $casts = [
        'total_recharge'      => 'decimal:2',
        'total_paid'          => 'decimal:2',
        'due_amount'          => 'decimal:2',
        'commission_amount'   => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'opening_balance'     => 'decimal:2',
        'closing_balance'     => 'decimal:2',
        'bandwidth_breakdown' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($bill) {
            if (empty($bill->bill_no)) {
                $lastBill = static::withoutGlobalScopes()->orderBy('id', 'desc')->first();
                $nextNumber = $lastBill ? $lastBill->id + 1 : 1;
                $bill->bill_no = 'RB-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function payments()
    {
        return $this->hasMany(ResellerBillPayment::class);
    }

    public function recharges()
    {
        return $this->hasMany(ResellerClientRecharge::class, 'reseller_id', 'reseller_id')
            ->where('bill_month', $this->bill_month ?? 0)
            ->where('bill_year', $this->bill_year ?? 0);
    }

    public function updateStatus()
    {
        if ($this->due_amount <= 0) {
            $this->status = 'paid';
        } elseif ($this->total_paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }
        $this->save();
    }

    public function recalculate()
    {
        $this->due_amount = $this->total_recharge - $this->total_paid - $this->discount_amount;
        $this->closing_balance = $this->opening_balance + $this->total_recharge - $this->total_paid - $this->discount_amount;
        $this->updateStatus();
    }
}
