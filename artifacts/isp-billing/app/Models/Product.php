<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Product extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'category_id', 'name', 'code', 'unit', 'purchase_price',
        'selling_price', 'stock_quantity', 'alert_quantity', 'description', 'is_active',
        'has_serial', 'warranty_months', 'brand', 'model', 'type', 'barcode_prefix',
        'company_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }
}
