<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class BillItem extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['bill_id', 'description', 'amount', 'type', 'company_id'];

    protected $casts = ['amount' => 'decimal:2'];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
