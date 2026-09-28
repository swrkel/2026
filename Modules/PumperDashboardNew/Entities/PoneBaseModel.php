<?php

namespace Modules\PumperDashboardNew\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class PoneBaseModel extends Model
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
