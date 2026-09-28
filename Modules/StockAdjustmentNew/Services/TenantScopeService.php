<?php

namespace Modules\StockAdjustmentNew\Services;

use Illuminate\Http\Request;

class TenantScopeService
{
    public function businessId(Request $request): ?int
    {
        $businessId = $request->session()->get('user.business_id')
            ?? $request->session()->get('business.id')
            ?? $request->session()->get('business_id')
            ?? optional(auth()->user())->business_id
            ?? 0;

        return (int) $businessId ?: null;
    }

    public function userId(): ?int
    {
        return auth()->id();
    }

    /**
     * null means the current user can access all business locations.
     *
     * @return array<int, int>|null
     */
    public function permittedLocationIds(Request $request): ?array
    {
        $locations = $request->session()->get('user.permitted_locations');

        if ($locations === null || $locations === 'all' || $locations === ['all']) {
            return null;
        }

        if (is_string($locations)) {
            $locations = array_filter(array_map('trim', explode(',', $locations)));
        }

        if (! is_array($locations)) {
            return null;
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($value): int => (int) $value,
            $locations
        ), static fn (int $value): bool => $value > 0)));
    }

    public function defaultLocationId(Request $request, array $availableLocations = []): ?int
    {
        $candidate = (int) (
            $request->old('location_id')
            ?? $request->session()->get('business.location_id')
            ?? $request->session()->get('user.location_id')
            ?? $request->session()->get('location_id')
            ?? 0
        );

        $availableIds = array_map(static fn (array $location): int => (int) $location['id'], $availableLocations);
        if ($candidate > 0 && ($availableIds === [] || in_array($candidate, $availableIds, true))) {
            return $candidate;
        }

        return $availableIds[0] ?? null;
    }

    public function defaultStoreId(Request $request, array $availableStores = [], ?int $locationId = null): ?int
    {
        $candidate = (int) (
            $request->old('store_id')
            ?? $request->session()->get('business.default_store')
            ?? $request->session()->get('user.store_id')
            ?? $request->session()->get('store_id')
            ?? 0
        );

        $eligible = array_values(array_filter($availableStores, static function (array $store) use ($locationId): bool {
            return $locationId === null
                || $store['location_id'] === null
                || (int) $store['location_id'] === $locationId;
        }));
        $eligibleIds = array_map(static fn (array $store): int => (int) $store['id'], $eligible);

        if ($candidate > 0 && ($eligibleIds === [] || in_array($candidate, $eligibleIds, true))) {
            return $candidate;
        }

        return $eligibleIds[0] ?? null;
    }

    public function applyBusinessScope($query, ?int $businessId)
    {
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        return $query;
    }
}
