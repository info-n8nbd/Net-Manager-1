<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationRouterSnapshot extends Model
{
    protected $fillable = [
        'migration_run_id', 'device_id', 'device_name', 'device_host',
        'username', 'profile', 'password', 'service', 'disabled', 'comment',
        'remote_address', 'last_logged_out',
        'billing_client_id', 'match_status', 'match_type',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'last_logged_out' => 'datetime',
    ];

    public function migrationRun()
    {
        return $this->belongsTo(MigrationRun::class);
    }

    public function device()
    {
        return $this->belongsTo(MikrotikDevice::class, 'device_id');
    }

    public function billingClient()
    {
        return $this->belongsTo(Client::class, 'billing_client_id');
    }
}
