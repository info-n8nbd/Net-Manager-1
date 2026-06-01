<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedTestLog extends Model
{
    protected $fillable = [
        'client_id',
        'download_speed',
        'upload_speed',
        'ping_ms',
        'server_location',
        'test_type',
    ];

    protected $casts = [
        'download_speed' => 'decimal:2',
        'upload_speed' => 'decimal:2',
        'ping_ms' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
