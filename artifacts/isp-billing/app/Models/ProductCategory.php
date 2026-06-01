<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ProductCategory extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['name', 'description', 'is_active', 'company_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
