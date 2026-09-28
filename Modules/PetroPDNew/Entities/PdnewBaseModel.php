<?php

namespace Modules\PetroPDNew\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class PdnewBaseModel extends Model
{
    protected $guarded = ['id'];

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where($this->qualifyColumn('business_id'), $businessId);
    }

    public function scopeForLocation(Builder $query, ?int $locationId): Builder
    {
        return $locationId
            ? $query->where($this->qualifyColumn('location_id'), $locationId)
            : $query;
    }
}
