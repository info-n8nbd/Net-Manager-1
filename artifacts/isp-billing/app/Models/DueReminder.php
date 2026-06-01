<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantAware;

class DueReminder extends Model
{
    use TenantAware;

    protected $fillable = [
        'bill_id', 'client_id', 'reminder_day', 'sms_sent', 'sent_at', 'message'
    ];

    protected $casts = [
        'sms_sent' => 'boolean',
        'sent_at' => 'datetime'
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
