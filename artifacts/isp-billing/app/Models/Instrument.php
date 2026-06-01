<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Instrument extends Model
{
    use TenantAware;

    protected $fillable = [
        'name', 'invoice_number', 'model', 'brand', 'serial_number', 'category', 'sub_category',
        'purchase_price', 'quantity', 'purchase_date', 'vendor_id', 'purchaser',
        'condition', 'status', 'assigned_to', 'warranty_expiry', 'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeInUse($query)
    {
        return $query->where('status', 'in_use');
    }
}
