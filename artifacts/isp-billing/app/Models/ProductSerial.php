<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSerial extends Model
{
    protected $fillable = [
        'product_id', 'serial_number', 'mac_address', 'status', 'client_id',
        'purchase_id', 'warranty_start', 'warranty_end', 'remarks'
    ];

    protected $casts = [
        'warranty_start' => 'date',
        'warranty_end' => 'date'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_end && $this->warranty_end->isFuture();
    }

    public function scopeInStock($query)
    {
        return $query->where('status', 'in_stock');
    }

    public function scopeUnderWarranty($query)
    {
        return $query->where('warranty_end', '>=', now());
    }
}
