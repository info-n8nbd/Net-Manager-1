<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminOverride extends Model
{
    protected $fillable = [
        'admin_id',
        'technician_action_log_id',
        'client_id',
        'override_type',
        'action_category',
        'before_values',
        'after_values',
        'correction_data',
        'reason',
        'status',
        'balance_adjustment',
        'network_sync_required',
        'network_sync_completed',
        'network_synced_at',
        'ip_address',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
        'correction_data' => 'array',
        'balance_adjustment' => 'decimal:2',
        'network_sync_required' => 'boolean',
        'network_sync_completed' => 'boolean',
        'network_synced_at' => 'datetime',
    ];

    const TYPE_EDIT = 'edit';
    const TYPE_CANCEL = 'cancel';
    const TYPE_REVERSE = 'reverse';
    const TYPE_CORRECT = 'correct';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function technicianActionLog(): BelongsTo
    {
        return $this->belongsTo(TechnicianActionLog::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function markNetworkSynced(): void
    {
        $this->update([
            'network_sync_completed' => true,
            'network_synced_at' => now(),
        ]);
    }

    public static function createOverride(
        int $adminId,
        TechnicianActionLog $actionLog,
        string $overrideType,
        array $afterValues,
        string $reason,
        float $balanceAdjustment = 0,
        bool $networkSyncRequired = false,
        ?array $correctionData = null
    ): self {
        return self::create([
            'admin_id' => $adminId,
            'technician_action_log_id' => $actionLog->id,
            'client_id' => $actionLog->client_id,
            'override_type' => $overrideType,
            'action_category' => $actionLog->action_category,
            'before_values' => $actionLog->action_data,
            'after_values' => $afterValues,
            'correction_data' => $correctionData,
            'reason' => $reason,
            'status' => self::STATUS_COMPLETED,
            'balance_adjustment' => $balanceAdjustment,
            'network_sync_required' => $networkSyncRequired,
            'network_sync_completed' => false,
            'ip_address' => request()->ip(),
        ]);
    }

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('action_category', $category);
    }

    public function scopePendingNetworkSync($query)
    {
        return $query->where('network_sync_required', true)
            ->where('network_sync_completed', false);
    }
}
