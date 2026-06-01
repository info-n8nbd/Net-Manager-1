<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class MikrotikProfile extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'name', 'rate_limit', 'download_speed', 'upload_speed', 'speed_unit',
        'local_address', 'remote_address', 'source_device', 'profile_type',
        'is_active', 'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'download_speed' => 'integer',
        'upload_speed' => 'integer',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class, 'mikrotik_profile_name', 'name');
    }

    public function getSpeedLabelAttribute(): string
    {
        if ($this->download_speed > 0 || $this->upload_speed > 0) {
            return $this->download_speed . '/' . $this->upload_speed . ' ' . $this->speed_unit;
        }
        return $this->rate_limit ?: 'N/A';
    }
}
