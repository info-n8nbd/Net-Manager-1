<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\TenantAware;

class Zone extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['name', 'description', 'is_active', 'company_id', 'upazila_id', 'reseller_id'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('hide-reseller-zones', function (Builder $builder) {
            if (auth()->check() && auth()->user()->user_type !== 'reseller') {
                $builder->whereNull('zones.reseller_id');
            }
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (auth()->check() && auth()->user()->user_type === 'reseller') {
            return $this->withoutGlobalScope('tenant')
                ->where($field ?? $this->getRouteKeyName(), $value)
                ->first();
        }
        return parent::resolveRouteBinding($value, $field);
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
