<?php
/**
 * Package Model
 * 
 * CRITICAL FOR MIKROTIK INTEGRATION:
 * ================================
 * Package NAME must EXACTLY match MikroTik PPP Profile name!
 * 
 * Examples:
 * - If billing package is "BASIC", MikroTik must have profile "BASIC"
 * - If billing package is "10Mbps", MikroTik must have profile "10Mbps"
 * - If billing package is "STANDARD", MikroTik must have profile "STANDARD"
 * 
 * The billing system does NOT manage bandwidth directly.
 * Speed control is done ONLY via MikroTik PPP profiles.
 * 
 * To create a new package:
 * 1. First create the PPP profile in MikroTik with desired speed
 * 2. Then create the package in billing with the SAME name
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\TenantAware;

class Package extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'name', 'code', 'price', 'btrc_amount', 'bandwidth', 'download_speed', 'upload_speed',
        'speed_unit', 'connection_type', 'validity_days', 'description', 'mikrotik_profile', 'is_active', 'show_in_public', 'company_id'
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && !auth()->user()->isSuperAdmin()) {
                $companyId = auth()->user()->company_id;
                if ($companyId) {
                    $builder->where(function ($q) use ($companyId) {
                        $q->where('packages.company_id', $companyId)
                          ->orWhereNull('packages.company_id');
                    });
                }
            }
        });
    }

    protected $casts = ['is_active' => 'boolean', 'show_in_public' => 'boolean', 'price' => 'decimal:2', 'btrc_amount' => 'decimal:2'];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function speeds()
    {
        return $this->hasMany(PackageSpeed::class);
    }

    public function boundProfile()
    {
        return $this->belongsTo(MikrotikProfile::class, 'mikrotik_profile', 'name');
    }
}
