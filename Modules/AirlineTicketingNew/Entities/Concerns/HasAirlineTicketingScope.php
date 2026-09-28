<?php

namespace Modules\AirlineTicketingNew\Entities\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasAirlineTicketingScope
{
    public function scopeForBusiness(Builder $query, ?int $businessId = null): Builder
    {
        return $query->where(
            $query->getModel()->getTable() . '.business_id',
            $businessId ?: (int) session('business.id')
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->getModel()->getTable() . '.is_active', true);
    }
}
