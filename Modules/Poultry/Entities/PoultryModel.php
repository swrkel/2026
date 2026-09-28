<?php

namespace Modules\Poultry\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Poultry\Support\BusinessContext;

/**
 * Base for every entity owned by this module.
 *
 * Extends Illuminate's Model directly rather than any core base class, so the
 * module carries no dependency on code outside Modules/Poultry.
 */
abstract class PoultryModel extends Model
{
    protected $guarded = ['id'];

    /**
     * Scope to the active tenant. Applied explicitly rather than as a global
     * scope: console commands and cross-tenant reports legitimately need to
     * read every business, and a global scope makes that awkward to opt out of.
     */
    public function scopeForBusiness(Builder $query, $businessId = null)
    {
        return $query->where(
            $this->getTable().'.business_id',
            $businessId ?: BusinessContext::id()
        );
    }

    public function scopeActive(Builder $query)
    {
        return $query->where($this->getTable().'.is_active', 1);
    }

    /**
     * Stamp tenant and author on create. Values already set are respected, so
     * importers and seeders can supply their own.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->business_id)) {
                $model->business_id = BusinessContext::id();
            }

            if (empty($model->created_by)) {
                $model->created_by = BusinessContext::userId();
            }
        });
    }
}
