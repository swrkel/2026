<?php

namespace Modules\RiceMill\Reports\Concerns;

use Illuminate\Http\Request;

trait AppliesOperationalScope
{
    /**
     * Apply optional Location / Store filters after tenant + business scope.
     * The controller validates these IDs against the current user/business
     * before any report reaches this method.
     */
    protected function applyOperationalScope($query, Request $request, ?string $qualifier = null)
    {
        $locationId = (int) $request->input('location_id', 0);
        $storeId = (int) $request->input('store_id', 0);

        $column = static function (string $name) use ($qualifier): string {
            return $qualifier ? $qualifier . '.' . $name : $name;
        };

        if ($locationId > 0) {
            $query->where($column('location_id'), $locationId);
        }

        if ($storeId > 0) {
            $query->where($column('store_id'), $storeId);
        }

        return $query;
    }

    protected function hasOperationalFilter(Request $request): bool
    {
        return (int) $request->input('location_id', 0) > 0
            || (int) $request->input('store_id', 0) > 0;
    }
}
