<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class SalaryPayment extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'salary_id', 'amount', 'payment_date', 'payment_method_id',
        'transaction_id', 'remarks', 'created_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function salary()
    {
        return $this->belongsTo(Salary::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
