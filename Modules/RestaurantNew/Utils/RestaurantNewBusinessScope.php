<?php

namespace Modules\RestaurantNew\Utils;

use Illuminate\Database\Eloquent\Builder;

class RestaurantNewBusinessScope
{
    public static function businessId(): ?int
    {
        return session('business.id') ? (int) session('business.id') : null;
    }

    public static function locationId(): ?int
    {
        return request()->filled('location_id') ? (int) request('location_id') : null;
    }

    public static function apply(Builder $query, ?string $businessColumn = 'business_id', ?string $locationColumn = 'location_id'): Builder
    {
        if ($businessColumn && self::businessId()) {
            $query->where($businessColumn, self::businessId());
        }

        if ($locationColumn && self::locationId()) {
            $query->where($locationColumn, self::locationId());
        }

        return $query;
    }
}
