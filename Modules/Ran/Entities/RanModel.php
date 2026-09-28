<?php

namespace Modules\Ran\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ran\Support\RanContext;

abstract class RanModel extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->business_id)) {
                $model->business_id = RanContext::businessId();
            }
            if (empty($model->created_by)) {
                $model->created_by = RanContext::userId();
            }
        });
        static::updating(function (self $model): void {
            $model->updated_by = RanContext::userId();
        });
        static::addGlobalScope('ran_business', function (Builder $builder): void {
            $businessId = RanContext::businessIdOrNull();
            if ($businessId) {
                $builder->where($builder->getModel()->getTable().'.business_id', $businessId);
            }
        });
    }

    public function scopeForLocation(Builder $query, ?int $locationId): Builder
    {
        return $locationId ? $query->where('location_id', $locationId) : $query;
    }

    public function scopeForStore(Builder $query, ?int $storeId): Builder
    {
        return $storeId ? $query->where('store_id', $storeId) : $query;
    }
}
