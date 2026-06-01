<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class MikrotikDevice extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'name', 'host', 'port', 'www_port', 'username', 'password', 'zone_id',
        'description', 'is_active', 'last_connected_at', 'pop_id', 'branch_id',
        'vpn_account_id', 'overwrite_ppp_comment', 'model', 'system_identity',
        'latitude', 'longitude', 'status', 'last_checked_at',
        'cpu_load', 'memory_usage', 'uptime', 'version', 'router_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'overwrite_ppp_comment' => 'boolean',
        'last_connected_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    protected $hidden = ['password'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function users()
    {
        return $this->hasMany(MikrotikUser::class);
    }

    public function queues()
    {
        return $this->hasMany(BandwidthQueue::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function pop()
    {
        return $this->belongsTo(Pop::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function vpnAccount()
    {
        return $this->belongsTo(VpnAccount::class);
    }
}
