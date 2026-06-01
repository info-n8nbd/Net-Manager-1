<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\TenantAware;

class Box extends Model
{
    use TenantAware;

    protected $fillable = [
        'name',
        'zone_id',
        'area_id',
        'location',
        'capacity',
        'description',
        'status',
        'company_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('hide-reseller-boxes', function (Builder $builder) {
            if (auth()->check() && auth()->user()->user_type !== 'reseller') {
                $builder->where(function ($q) {
                    $q->whereHas('zone', fn($zq) => $zq->withoutGlobalScopes()->whereNull('reseller_id'))
                      ->orWhereNull('zone_id');
                });
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

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function usedCapacity(): int
    {
        return $this->clients()->where('status', 'active')->count();
    }

    public function availableCapacity(): int
    {
        return max(0, $this->capacity - $this->usedCapacity());
    }
}
