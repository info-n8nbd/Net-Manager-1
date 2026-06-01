<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPackage extends Model
{
    protected $fillable = [
        'name', 'credits', 'price', 'currency', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    public function getPricePerCreditAttribute(): float
    {
        return $this->credits > 0 ? round($this->price / $this->credits, 4) : 0;
    }

    public function getSavingsPercentAttribute(): float
    {
        $basePrice = $this->credits * 1.0; // 1 credit = $1 base
        if ($basePrice <= 0) return 0;
        return round((1 - $this->price / $basePrice) * 100, 1);
    }
}
