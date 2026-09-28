<?php

namespace Modules\StockTakingNew\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

class TenantScopeService
{
    public function businessId(?Request $request = null): ?int
    {
        $request ??= request();
        $value = $request->session()->get('user.business_id')
            ?? $request->session()->get('business.id')
            ?? optional(auth()->user())->business_id
            ?? 0;

        return (int) $value ?: null;
    }

    public function userId(): ?int
    {
        return auth()->id();
    }

    /** @return array<int>|string */
    public function permittedLocations()
    {
        if (! auth()->check() || app()->runningInConsole()) {
            return 'all';
        }

        try {
            $locations = auth()->user()->permitted_locations();
            if ($locations === 'all') {
                return 'all';
            }
            return array_values(array_unique(array_map('intval', (array) $locations)));
        } catch (\Throwable) {
            return [];
        }
    }

    public function canAccessLocation(?int $locationId): bool
    {
        if (! $locationId) {
            return false;
        }
        $permitted = $this->permittedLocations();
        return $permitted === 'all' || in_array($locationId, $permitted, true);
    }

    public function assertLocationAccess(?int $locationId): void
    {
        abort_unless($this->canAccessLocation($locationId), 403, 'You do not have access to this business location.');
    }

    public function assertBusinessRecord($record, ?int $businessId): void
    {
        if (! $businessId || (int) $record->business_id !== $businessId) {
            abort(404);
        }
        if (isset($record->location_id) && $record->location_id !== null) {
            $this->assertLocationAccess((int) $record->location_id);
        }
    }

    public function applyLocationScope($query, string $column = 'location_id')
    {
        $permitted = $this->permittedLocations();
        if ($permitted !== 'all') {
            $query->whereIn($column, $permitted ?: [-1]);
        }
        return $query;
    }

    public function permittedLocationIdsForFilters(): ?array
    {
        $permitted = $this->permittedLocations();
        return $permitted === 'all' ? null : $permitted;
    }
}
