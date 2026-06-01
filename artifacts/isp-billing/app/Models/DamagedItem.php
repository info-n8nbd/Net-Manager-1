<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamagedItem extends Model
{
    protected $fillable = [
        'product_id', 'serial_id', 'quantity', 'damage_date',
        'damage_reason', 'status', 'reported_by'
    ];

    protected $casts = [
        'damage_date' => 'date'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'serial_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
