<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TechnicianActionLog extends Model
{
    protected $fillable = [
        'technician_id',
        'client_id',
        'action_type',
        'action_category',
        'reference_type',
        'reference_id',
        'action_data',
        'before_values',
        'after_values',
        'status',
        'is_overridden',
        'overridden_by',
        'overridden_at',
        'ip_address',
        'notes',
    ];

    protected $casts = [
        'action_data' => 'array',
        'before_values' => 'array',
        'after_values' => 'array',
        'is_overridden' => 'boolean',
        'overridden_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REVERSED = 'reversed';
    const STATUS_CORRECTED = 'corrected';

    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_CASH = 'cash_settlement';
    const CATEGORY_BILLING = 'billing';
    const CATEGORY_CLIENT_STATUS = 'client_status';
    const CATEGORY_CLIENT_DATA = 'client_data';
    const CATEGORY_NETWORK = 'network';

    const TYPE_PAYMENT_SUBMIT = 'payment_submit';
    const TYPE_PAYMENT_COLLECT = 'payment_collect';
    const TYPE_CASH_SUBMIT = 'cash_submit';
    const TYPE_CASH_RECEIVE = 'cash_receive';
    const TYPE_BILL_SUBMIT = 'bill_submit';
    const TYPE_BILL_CANCEL = 'bill_cancel';
    const TYPE_CLIENT_SUSPEND = 'client_suspend';
    const TYPE_CLIENT_RESTORE = 'client_restore';
    const TYPE_CLIENT_NOTE = 'client_note';
    const TYPE_CLIENT_DOCUMENT = 'client_document';
    const TYPE_NETWORK_RECONNECT = 'network_reconnect';
    const TYPE_NETWORK_DISCONNECT = 'network_disconnect';

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function overriddenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    public function adminOverride(): HasOne
    {
        return $this->hasOne(AdminOverride::class);
    }

    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            return $this->reference_type::find($this->reference_id);
        }
        return null;
    }

    public function markOverridden(int $adminId, string $newStatus = self::STATUS_CORRECTED): void
    {
        $this->update([
            'is_overridden' => true,
            'overridden_by' => $adminId,
            'overridden_at' => now(),
            'status' => $newStatus,
        ]);
    }

    public static function logAction(
        int $technicianId,
        string $actionType,
        string $category,
        array $actionData,
        ?int $clientId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $beforeValues = null,
        ?array $afterValues = null,
        ?string $notes = null
    ): self {
        return self::create([
            'technician_id' => $technicianId,
            'client_id' => $clientId,
            'action_type' => $actionType,
            'action_category' => $category,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'action_data' => $actionData,
            'before_values' => $beforeValues,
            'after_values' => $afterValues,
            'status' => self::STATUS_ACTIVE,
            'ip_address' => request()->ip(),
            'notes' => $notes,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOverrideable($query)
    {
        return $query->where('is_overridden', false)->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('action_category', $category);
    }

    public function scopeByTechnician($query, int $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }
}
