<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class MonthClose extends Model
{
    use TenantAware;

    protected $fillable = [
        'billing_month',
        'billing_year',
        'status',
        'total_billed',
        'total_collected',
        'total_due',
        'total_advance',
        'active_clients',
        'new_connections',
        'disconnections',
        'closed_by',
        'closed_at',
        'notes',
        'summary_data',
    ];

    protected $casts = [
        'total_billed' => 'decimal:2',
        'total_collected' => 'decimal:2',
        'total_due' => 'decimal:2',
        'total_advance' => 'decimal:2',
        'closed_at' => 'datetime',
        'summary_data' => 'array',
    ];

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public static function isMonthClosed(int $month, int $year): bool
    {
        return static::where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('status', 'closed')
            ->exists();
    }

    public static function canModifyBill(Bill $bill): bool
    {
        return !static::isMonthClosed($bill->billing_month, $bill->billing_year);
    }
}
