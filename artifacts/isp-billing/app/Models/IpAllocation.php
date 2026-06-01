<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_pool_id', 'ip_address', 'status', 'client_id', 'assigned_by',
        'mac_address', 'hostname', 'notes', 'assigned_at', 'released_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function pool()
    {
        return $this->belongsTo(IpPool::class, 'ip_pool_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
