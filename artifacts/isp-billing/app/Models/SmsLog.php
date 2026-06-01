<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class SmsLog extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'phone', 'message', 'status', 'event_type', 'recipient_type',
        'response', 'skip_reason', 'client_id', 'sent_by'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';

    const EVENT_MONTHLY_BILL = 'monthly_bill';
    const EVENT_BILL_CONFIRMATION = 'bill_confirmation';
    const EVENT_PAYMENT_CONFIRMATION = 'payment_confirmation';
    const EVENT_SERVICE_SUSPENSION = 'service_suspension';
    const EVENT_SERVICE_RESTORATION = 'service_restoration';
    const EVENT_CUSTOM = 'custom';

    const RECIPIENT_CLIENT = 'client';
    const RECIPIENT_TECHNICIAN = 'technician';
    const RECIPIENT_ADMIN = 'admin';

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', self::STATUS_SKIPPED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
