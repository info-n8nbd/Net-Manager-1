<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ResellerPackage extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['reseller_id', 'package_id', 'reseller_price', 'selling_price', 'is_active', 'company_id'];

    protected $casts = [
        'reseller_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
