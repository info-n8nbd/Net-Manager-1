<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortForward extends Model
{
    protected $fillable = [
        'client_id', 'name', 'protocol', 'external_port',
        'internal_ip', 'internal_port', 'wan_interface', 'is_active',
        'mikrotik_device_id', 'mikrotik_rule_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function mikrotikDevice()
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
