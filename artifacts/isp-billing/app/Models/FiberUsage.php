<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiberUsage extends Model
{
    protected $table = 'fiber_usages';

    protected $fillable = [
        'fiber_code_id', 'fiber_stock_id', 'used_meter', 'client_id',
        'technician_id', 'note', 'used_date'
    ];

    protected $casts = [
        'used_date' => 'date',
    ];

    public function fiberStock()
    {
        return $this->belongsTo(FiberStock::class, 'fiber_stock_id');
    }

    public function fiberCode()
    {
        return $this->belongsTo(FiberStock::class, 'fiber_code_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
