<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikrotikSyncItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_run_id', 'username', 'client_id', 'diff_type',
        'router_profile', 'billing_profile', 'router_service',
        'router_disabled', 'billing_status', 'client_name', 'client_code',
        'proposed_action', 'action_status', 'action_message', 'action_applied_at',
    ];

    protected $casts = [
        'router_disabled' => 'boolean',
        'action_applied_at' => 'datetime',
    ];

    public function syncRun()
    {
        return $this->belongsTo(MikrotikSyncRun::class, 'sync_run_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function isPending()
    {
        return $this->action_status === 'pending' && $this->proposed_action !== null;
    }

    public function isApplied()
    {
        return $this->action_status === 'applied';
    }

    public function isFailed()
    {
        return $this->action_status === 'failed';
    }

    public function isSkipped()
    {
        return $this->action_status === 'skipped';
    }
}
