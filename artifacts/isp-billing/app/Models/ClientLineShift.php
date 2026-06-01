<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantAware;

class ClientLineShift extends Model
{
    use TenantAware;

    protected $fillable = [
        'client_id', 'old_address', 'new_address', 'old_zone', 'new_zone',
        'shift_charge', 'shift_date', 'remarks', 'processed_by'
    ];

    protected $casts = [
        'shift_date' => 'date',
        'shift_charge' => 'decimal:2'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
