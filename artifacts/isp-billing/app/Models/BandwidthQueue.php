<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BandwidthQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'mikrotik_device_id', 'name', 'target', 'max_limit', 'burst_limit',
        'burst_threshold', 'burst_time', 'priority', 'is_active'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function device()
    {
        return $this->belongsTo(MikrotikDevice::class, 'mikrotik_device_id');
    }
}
