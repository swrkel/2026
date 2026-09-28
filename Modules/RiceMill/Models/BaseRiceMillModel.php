<?php
namespace Modules\RiceMill\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Modules\RiceMill\Services\TenantContext;

abstract class BaseRiceMillModel extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->business_id && app()->bound(TenantContext::class)) {
                try { $model->business_id = app(TenantContext::class)->businessId(); } catch (\Throwable $e) {}
            }
        });
    }

    public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where($this->getTable().'.business_id',$businessId); }
}
