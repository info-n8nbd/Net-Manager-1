<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\TenantAware;

class Area extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['zone_id', 'name', 'description', 'is_active', 'company_id', 'reseller_id'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('hide-reseller-zones', function (Builder $builder) {
            if (auth()->check() && auth()->user()->user_type !== 'reseller') {
                $builder->whereNull('areas.reseller_id');
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

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function boxes()
    {
        return $this->hasMany(Box::class);
    }
}
