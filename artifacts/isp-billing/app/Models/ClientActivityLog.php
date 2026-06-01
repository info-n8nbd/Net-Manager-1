<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ClientActivityLog extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'client_id',
        'action_type',
        'old_value',
        'new_value',
        'reason',
        'triggered_by',
        'performed_by',
        'ip_address',
        'extra_data'
    ];

    protected $casts = [
        'extra_data' => 'array'
    ];

    const ACTION_ACTIVATED = 'activated';
    const ACTION_DEACTIVATED = 'deactivated';
    const ACTION_SUSPENDED = 'inactive';
    const ACTION_RECONNECTED = 'reconnected';
    const ACTION_STATUS_CHANGE = 'status_change';
    const ACTION_BILL_GENERATED = 'bill_generated';
    const ACTION_PAYMENT_RECEIVED = 'payment_received';
    const ACTION_CONNECTION_CREATED = 'connection_created';
    const ACTION_PACKAGE_CHANGED = 'package_changed';

    const TRIGGER_MANUAL = 'manual';
    const TRIGGER_SYSTEM = 'system';
    const TRIGGER_AUTO = 'auto';

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function performedByUser()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->action_type) {
            self::ACTION_ACTIVATED => 'Activated',
            self::ACTION_DEACTIVATED => 'Deactivated',
            self::ACTION_SUSPENDED => 'Suspended',
            self::ACTION_RECONNECTED => 'Reconnected',
            self::ACTION_STATUS_CHANGE => 'Status Changed',
            self::ACTION_BILL_GENERATED => 'Bill Generated',
            self::ACTION_PAYMENT_RECEIVED => 'Payment Received',
            self::ACTION_CONNECTION_CREATED => 'Connection Created',
            self::ACTION_PACKAGE_CHANGED => 'Package Changed',
            default => ucfirst(str_replace('_', ' ', $this->action_type))
        };
    }

    public function getActionColorAttribute(): string
    {
        return match($this->action_type) {
            self::ACTION_ACTIVATED, self::ACTION_RECONNECTED => 'success',
            self::ACTION_DEACTIVATED, self::ACTION_SUSPENDED => 'danger',
            self::ACTION_BILL_GENERATED => 'info',
            self::ACTION_PAYMENT_RECEIVED => 'primary',
            self::ACTION_CONNECTION_CREATED => 'success',
            default => 'secondary'
        };
    }
}
