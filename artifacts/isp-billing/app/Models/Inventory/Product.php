<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantAware;

class Product extends Model
{
    use SoftDeletes, TenantAware;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'type',
        'brand',
        'model',
        'unit',
        'barcode_prefix',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'alert_quantity',
        'description',
        'has_serial',
        'warranty_months',
        'is_active'
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function serials()
    {
        return $this->hasMany(\App\Models\ProductSerial::class, 'product_id');
    }
}
