<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsSchedule extends Model
{
    protected $fillable = [
        'sms_template_id', 'name', 'schedule_type', 'days_before', 'days_after',
        'send_time', 'target_filter', 'is_active', 'last_run', 'next_run'
    ];

    protected $casts = [
        'target_filter' => 'array',
        'is_active' => 'boolean',
        'last_run' => 'datetime',
        'next_run' => 'datetime'
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'sms_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueToRun($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_run')
                    ->orWhere('next_run', '<=', now());
            });
    }
}
