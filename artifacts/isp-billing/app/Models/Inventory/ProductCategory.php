<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantAware;

class ProductCategory extends Model
{
    use SoftDeletes, TenantAware;

    protected $table = 'product_categories';

    protected $fillable = [
        'name',
        'is_active'
    ];
}
