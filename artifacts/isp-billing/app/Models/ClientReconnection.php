<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantAware;

class ClientReconnection extends Model
{
    use TenantAware;

    protected $fillable = [
        'client_id', 'disconnect_date', 'reconnect_date', 'reconnection_fee',
        'disconnect_reason', 'remarks', 'processed_by'
    ];

    protected $casts = [
        'disconnect_date' => 'date',
        'reconnect_date' => 'date',
        'reconnection_fee' => 'decimal:2'
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
