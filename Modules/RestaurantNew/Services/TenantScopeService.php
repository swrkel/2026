<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function currentLocationId(?Request $request = null): ?int
    {
        $request ??= request();
        $value = $request->input('location_id')
            ?? $request->input('business_location_id')
            ?? $request->session()->get('business.location_id')
            ?? $request->session()->get('user.location_id')
            ?? $request->session()->get('business_location_id')
            ?? $request->session()->get('location_id')
            ?? 0;
        $locationId = (int) $value ?: null;

        if ($locationId && $this->canAccessLocation($locationId)) {
            return $locationId;
        }

        $allowed = $this->permittedLocations();
        if (is_array($allowed) && count($allowed) === 1) {
            return (int) reset($allowed);
        }

        return null;
    }

    public function userId(): ?int
    {
        return auth()->id();
    }

    public function permittedLocations()
    {
        if (! auth()->check() || app()->runningInConsole()) {
            return 'all';
        }

        try {
            $value = auth()->user()->permitted_locations();
            return $value === 'all' ? 'all' : array_values(array_unique(array_map('intval', (array) $value)));
        } catch (\Throwable) {
            return [];
        }
    }

    public function locationOptions(): Collection
    {
        $businessId = $this->businessId();
        if (! $businessId || ! DB::getSchemaBuilder()->hasTable('business_locations')) {
            return collect();
        }

        $query = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->select('id', 'name', 'location_id')
            ->orderBy('name');
        $allowed = $this->permittedLocations();
        if ($allowed !== 'all') {
            $query->whereIn('id', $allowed ?: [-1]);
        }

        return $query->get();
    }

    public function canAccessLocation(?int $locationId): bool
    {
        if (! $locationId) {
            return true;
        }
        $allowed = $this->permittedLocations();

        return $allowed === 'all' || in_array($locationId, $allowed, true);
    }

    public function assertLocationAccess(?int $locationId): void
    {
        abort_unless($this->canAccessLocation($locationId), 403, 'You do not have access to this business location.');
        if ($locationId && DB::getSchemaBuilder()->hasTable('business_locations')) {
            abort_unless(DB::table('business_locations')->where('business_id', $this->businessId())->where('id', $locationId)->exists(), 403, 'The selected location does not belong to this business.');
        }
    }

    public function applyLocationScope($query, string $column = 'location_id')
    {
        $allowed = $this->permittedLocations();
        if ($allowed !== 'all') {
            $query->whereIn($column, $allowed ?: [-1]);
        }

        return $query;
    }

    /**
     * Scope records that may either be shared business-wide (NULL location)
     * or assigned to a location the current user may access.
     */
    public function applyOptionalLocationScope($query, string $column = 'location_id', ?int $locationId = null)
    {
        $locationId ??= $this->currentLocationId();

        if ($locationId) {
            return $query->where(function ($locationQuery) use ($column, $locationId) {
                $locationQuery->whereNull($column)->orWhere($column, $locationId);
            });
        }

        $allowed = $this->permittedLocations();
        if ($allowed !== 'all') {
            $query->where(function ($locationQuery) use ($column, $allowed) {
                $locationQuery->whereNull($column)->orWhereIn($column, $allowed ?: [-1]);
            });
        }

        return $query;
    }

    public function assertBusinessRecord($record, ?int $businessId): void
    {
        abort_unless($businessId && (int) $record->business_id === $businessId, 404);
        if (isset($record->location_id) && $record->location_id !== null) {
            $this->assertLocationAccess((int) $record->location_id);
        }
    }
}
