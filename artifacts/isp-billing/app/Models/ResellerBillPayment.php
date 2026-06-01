<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ResellerBillPayment extends Model
{
    use TenantAware;

    protected $fillable = [
        'reseller_id', 'reseller_bill_id', 'amount', 'commission_amount',
        'discount_amount', 'payment_method', 'payment_reference',
        'payment_date', 'notes', 'received_by', 'company_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function bill()
    {
        return $this->belongsTo(ResellerBill::class, 'reseller_bill_id');
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
