<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class OltDevice extends Model
{
    use TenantAware;

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'ssh_port',
        'telnet_port',
        'http_port',
        'username',
        'password',
        'vendor',
        'pon_type',
        'connection_type',
        'model',
        'location',
        'total_ports',
        'is_active',
        'status',
        'last_sync',
        'notes',
        'snmp_community',
        'snmp_write_community',
        'snmp_port',
        'snmp_enabled',
        'pop_id',
        'branch_id',
        'cpu_usage',
        'memory_usage',
        'temperature',
        'system_description',
        'firmware_version',
        'uptime_seconds',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'snmp_enabled' => 'boolean',
        'last_sync' => 'datetime',
        'total_ports' => 'integer',
        'port' => 'integer',
    ];

    const CONNECTION_TYPES = [
        'telnet' => 'Telnet',
        'ssh' => 'SSH',
        'http' => 'HTTP (Web)',
        'snmp' => 'SNMP',
    ];

    const VENDORS = [
        'huawei' => 'Huawei',
        'zte' => 'ZTE',
        'bdcom' => 'BDCOM',
        'vsol' => 'VSOL',
        'fiberhome' => 'FiberHome',
        'cdata' => 'C-Data',
        'solitine' => 'Solitine',
        'gcom' => 'GCOM',
        'dbc_v' => 'DBC-V',
        'dbc_b' => 'DBC-B',
        'netlink' => 'Netlink',
        'other' => 'Other',
    ];

    const PON_TYPES = [
        'epon' => 'EPON',
        'gpon' => 'GPON',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class, 'olt_id');
    }

    public function pop()
    {
        return $this->belongsTo(Pop::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getOnuCountAttribute(): int
    {
        return \Illuminate\Support\Facades\DB::table('onus')->where('olt_device_id', $this->id)->count();
    }

    public function getOnlineOnuCountAttribute(): int
    {
        return \Illuminate\Support\Facades\DB::table('onus')->where('olt_device_id', $this->id)->where('status', 'online')->count();
    }

    public function getOfflineOnuCountAttribute(): int
    {
        return \Illuminate\Support\Facades\DB::table('onus')->where('olt_device_id', $this->id)->where('status', 'offline')->count();
    }

    public function getVendorNameAttribute(): string
    {
        return self::VENDORS[$this->vendor] ?? $this->vendor;
    }

    public function getPonTypeNameAttribute(): string
    {
        return self::PON_TYPES[$this->pon_type] ?? strtoupper($this->pon_type ?? 'EPON');
    }
}
