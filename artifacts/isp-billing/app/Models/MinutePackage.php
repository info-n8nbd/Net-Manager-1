<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MinutePackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'duration_minutes', 'price', 'bandwidth_up_mbps',
        'bandwidth_down_mbps', 'connection_type', 'is_active', 'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function sales()
    {
        return $this->hasMany(MinuteSale::class);
    }
}
