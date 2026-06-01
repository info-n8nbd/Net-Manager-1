<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class DailyCashClose extends Model
{
    use TenantAware;

    protected $fillable = [
        'close_date',
        'staff_user_id',
        'opening_balance',
        'total_collection',
        'total_submitted',
        'closing_balance',
        'cash_in_hand',
        'discrepancy',
        'status',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'close_date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_collection' => 'decimal:2',
        'total_submitted' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'cash_in_hand' => 'decimal:2',
        'discrepancy' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function getOpeningBalance(int $staffId, $date): float
    {
        $lastClose = static::where('staff_user_id', $staffId)
            ->where('close_date', '<', $date)
            ->orderBy('close_date', 'desc')
            ->first();

        return $lastClose ? (float) $lastClose->closing_balance : 0;
    }
}
