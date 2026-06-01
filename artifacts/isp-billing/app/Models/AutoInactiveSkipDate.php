<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class AutoInactiveSkipDate extends Model
{
    use TenantAware;

    protected $fillable = [
        'company_id',
        'skip_date',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'skip_date' => 'date',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function isSkipDate(string $date): bool
    {
        return static::whereDate('skip_date', $date)->exists();
    }
}
