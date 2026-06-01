<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiberHistory extends Model
{
    protected $table = 'fiber_histories';

    protected $fillable = [
        'type', 'fiber_stock_id', 'meter', 'details', 'note', 'date'
    ];

    protected $casts = [
        'date' => 'date',
        'meter' => 'decimal:2',
    ];

    public function fiberStock()
    {
        return $this->belongsTo(FiberStock::class, 'fiber_stock_id');
    }
}
