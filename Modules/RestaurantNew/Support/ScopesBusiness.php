<?php
namespace Modules\RestaurantNew\Support;
use Illuminate\Database\Eloquent\Builder;
use Modules\RestaurantNew\Services\TenantScopeService;
trait ScopesBusiness
{
    protected static function bootScopesBusiness(): void
    {
        static::addGlobalScope('restnew_business', function (Builder $builder): void {
            try {
                $id = app(TenantScopeService::class)->businessId();
                if ($id) $builder->where($builder->getModel()->qualifyColumn('business_id'), $id);
            } catch (\Throwable) {}
        });
        static::creating(function ($model): void {
            if (!$model->business_id) {
                try { $model->business_id = app(TenantScopeService::class)->businessId(); } catch (\Throwable) {}
            }
        });
    }
}
