<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantAware;

class Vendor extends Model
{
    use SoftDeletes, TenantAware;

    protected $table = 'vendors';

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'address',
        'contact_person',
        'balance',
        'opening_balance',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'balance' => 'decimal:2'
    ];

    public function purchases()
    {
        return $this->hasMany(\App\Models\Inventory\Purchase::class, 'vendor_id');
    }
}
