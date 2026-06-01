<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TenantAware
{
    public static function bootTenantAware(): void
    {
        static::creating(function ($model) {
            if (!$model->company_id && auth()->check() && auth()->user()->company_id) {
                $model->company_id = auth()->user()->company_id;
            }
            if (!$model->company_id && app()->runningInConsole()) {
                \Illuminate\Support\Facades\Log::warning(
                    'TenantAware: Model ' . get_class($model) . ' created without company_id in console context. '
                    . 'Set company_id explicitly in your command or job.'
                );
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && !auth()->user()->isSuperAdmin()) {
                $companyId = auth()->user()->company_id;
                if ($companyId) {
                    $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
                }
            }
        });
    }
}
