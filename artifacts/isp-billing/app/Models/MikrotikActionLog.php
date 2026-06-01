<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikActionLog extends Model
{
    const ACTION_ENABLE = 'enable';
    const ACTION_DISABLE = 'disable';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    
    const TYPE_PPPOE = 'pppoe';
    const TYPE_HOTSPOT = 'hotspot';
    const TYPE_QUEUE = 'queue';
    
    const TRIGGER_AUTO_SUSPEND = 'auto_suspend';
    const TRIGGER_AUTO_RESTORE = 'auto_restore';
    const TRIGGER_PAYMENT = 'payment';
    const TRIGGER_ADMIN = 'admin';
    const TRIGGER_MANUAL = 'manual';
    const TRIGGER_BILLING = 'billing';
    const TRIGGER_DUE_EXTENSION = 'due_extension';
    
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRYING = 'retrying';
    
    const MAX_RETRIES = 3;

    protected $fillable = [
        'client_id', 'device_id', 'action', 'action_type', 'username',
        'router_ip', 'trigger_source', 'triggered_by', 'status',
        'retry_count', 'error_message', 'request_data', 'response_data',
        'executed_at', 'last_retry_at'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'executed_at' => 'datetime',
        'last_retry_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MikrotikDevice::class, 'device_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function markSuccess(array $response = []): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_data' => $response,
            'executed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'executed_at' => now(),
        ]);
    }

    public function markRetrying(): void
    {
        $this->update([
            'status' => self::STATUS_RETRYING,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);
    }

    public function canRetry(): bool
    {
        return $this->retry_count < self::MAX_RETRIES && $this->status === self::STATUS_FAILED;
    }

    public static function log(
        ?Client $client,
        ?MikrotikDevice $device,
        string $action,
        string $actionType,
        string $triggerSource,
        ?int $triggeredBy = null,
        array $requestData = []
    ): self {
        return self::create([
            'client_id' => $client?->id,
            'device_id' => $device?->id,
            'action' => $action,
            'action_type' => $actionType,
            'username' => $client?->pppoe_username,
            'router_ip' => $device?->ip_address ?? $device?->host,
            'trigger_source' => $triggerSource,
            'triggered_by' => $triggeredBy ?? auth()->id(),
            'status' => self::STATUS_PENDING,
            'request_data' => $requestData,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_FAILED)
            ->where('retry_count', '<', self::MAX_RETRIES);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_PENDING => 'warning',
            self::STATUS_RETRYING => 'info',
            default => 'secondary',
        };
    }
}
