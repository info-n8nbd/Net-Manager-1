<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class CardDistributor extends Model
{
    use TenantAware;

    protected $fillable = [
        'user_id', 'name', 'phone', 'email', 'address',
        'commission_rate', 'total_sales', 'total_commission', 'balance', 'is_active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cards()
    {
        return $this->hasMany(ScratchCard::class, 'distributor_id');
    }

    public function transactions()
    {
        return $this->hasMany(CardTransaction::class, 'distributor_id');
    }
}
