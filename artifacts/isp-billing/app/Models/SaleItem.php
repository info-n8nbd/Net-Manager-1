<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class SaleItem extends Model
{
    use TenantAware;

    protected $fillable = [
        'company_id', 'sale_id', 'product_id', 'product_name', 'quantity',
        'unit_price', 'total', 'serial_number', 'brand', 'model'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
