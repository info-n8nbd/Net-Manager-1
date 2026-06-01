<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiberStock extends Model
{
    protected $table = 'fiber_stocks';

    protected $fillable = [
        'name', 'vendor_id', 'purchaser', 'invoice_number', 'fiber_code', 'brand_name', 'fiber_type', 'core',
        'start_meter', 'end_meter', 'total_meter', 'remaining_meter',
        'per_meter_price', 'total_cost', 'stock_date', 'notes', 'status'
    ];

    protected $casts = [
        'stock_date' => 'date',
        'start_meter' => 'decimal:2',
        'end_meter' => 'decimal:2',
        'total_meter' => 'decimal:2',
        'remaining_meter' => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function usages()
    {
        return $this->hasMany(FiberUsage::class, 'fiber_stock_id');
    }

    public function histories()
    {
        return $this->hasMany(FiberHistory::class, 'fiber_stock_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->remaining_meter > 0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('remaining_meter', '>', 0);
    }
}
