<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantAware;

class IpPool extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'name', 'network_address', 'subnet_mask', 'cidr_prefix', 'ip_version',
        'gateway', 'dns_primary', 'dns_secondary', 'pool_type', 'branch_id',
        'zone_id', 'pop_id', 'mikrotik_device_id', 'total_ips', 'used_ips',
        'reserved_ips', 'description', 'is_active', 'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function pop()
    {
        return $this->belongsTo(Pop::class);
    }

    public function allocations()
    {
        return $this->hasMany(IpAllocation::class);
    }

    public function getAvailableCountAttribute()
    {
        return $this->total_ips - $this->used_ips - $this->reserved_ips;
    }
}
