<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScratchCardBatch extends Model
{
    protected $fillable = [
        'batch_no', 'name', 'package_id', 'amount', 'validity_days',
        'total_cards', 'used_cards', 'active_cards', 'expired_cards',
        'status', 'created_by', 'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function cards()
    {
        return $this->hasMany(ScratchCard::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateBatchNo(): string
    {
        $prefix = 'BATCH-' . date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
