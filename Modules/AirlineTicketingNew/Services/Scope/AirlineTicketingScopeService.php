<?php

namespace Modules\AirlineTicketingNew\Services\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AirlineTicketingScopeService
{
    public function businessId(): int
    {
        return (int) session('business.id');
    }

    public function locationId(?Request $request = null): ?int
    {
        $value = $request?->integer('business_location_id')
            ?: session('user.business_location_id');

        return $value ? (int) $value : null;
    }

    public function storeId(?Request $request = null): ?int
    {
        $value = $request?->integer('store_id') ?: session('user.store_id');

        return $value ? (int) $value : null;
    }

    public function apply(Builder $query, ?Request $request = null): Builder
    {
        $query->where($query->getModel()->getTable() . '.business_id', $this->businessId());

        if ($locationId = $this->locationId($request)) {
            $query->where($query->getModel()->getTable() . '.business_location_id', $locationId);
        }

        if ($storeId = $this->storeId($request)) {
            $query->where($query->getModel()->getTable() . '.store_id', $storeId);
        }

        return $query;
    }
}
