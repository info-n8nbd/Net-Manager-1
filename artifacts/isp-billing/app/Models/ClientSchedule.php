<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ClientSchedule extends Model
{
    use TenantAware;

    protected $fillable = [
        'client_id', 'action', 'scheduled_at', 'status', 'package_id',
        'mikrotik_profile', 'note', 'created_by', 'executed_at',
        'execution_result', 'company_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')->where('scheduled_at', '<=', now());
    }
}
