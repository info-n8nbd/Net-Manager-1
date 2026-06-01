<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikrotikSyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id', 'status', 'mode', 'initiated_by',
        'started_at', 'completed_at',
        'router_user_count', 'billing_user_count', 'synced_count',
        'only_on_router_count', 'only_in_billing_count', 'mismatch_count',
        'actions_applied', 'actions_failed',
        'error_message', 'summary',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'summary' => 'array',
    ];

    public function device()
    {
        return $this->belongsTo(MikrotikDevice::class, 'device_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function items()
    {
        return $this->hasMany(MikrotikSyncItem::class, 'sync_run_id');
    }

    public function ghostUsers()
    {
        return $this->items()->where('diff_type', 'only_on_router');
    }

    public function billingOnly()
    {
        return $this->items()->where('diff_type', 'only_in_billing');
    }

    public function mismatches()
    {
        return $this->items()->where('diff_type', 'mismatch');
    }

    public function matched()
    {
        return $this->items()->where('diff_type', 'synced');
    }

    public function pendingActions()
    {
        return $this->items()->where('action_status', 'pending')->whereNotNull('proposed_action');
    }
}
