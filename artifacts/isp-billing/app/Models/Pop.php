<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantAware;

class Pop extends Model
{
    use HasFactory, SoftDeletes;
    use TenantAware;

    protected $fillable = [
        "reseller_id", "pop_source",
        "name", "code", "branch_id", "zone_id", "address", "contact_person",
        "contact_phone", "total_bandwidth_mbps", "used_bandwidth_mbps",
        "uplink_provider", "uplink_type", "mikrotik_device_id", "status", "metadata",
    ];

    protected $casts = [
        "metadata" => "json",
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function mikrotikDevice()
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function ipPools()
    {
        return $this->hasMany(IpPool::class);
    }

    public function getSourceLabelAttribute(): string
    {
        return match($this->pop_source) {
            "mac_reseller"       => "MAC Reseller",
            "bandwidth_reseller" => "Bandwidth Reseller",
            default              => "Standalone",
        };
    }

    public function getSourceBadgeAttribute(): string
    {
        return match($this->pop_source) {
            "mac_reseller"       => "badge-secondary",
            "bandwidth_reseller" => "badge-primary",
            default              => "badge-success",
        };
    }
}
