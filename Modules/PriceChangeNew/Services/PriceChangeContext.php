<?php

namespace Modules\PriceChangeNew\Services;

use App\BusinessLocation;
use App\Services\BusinessLocationAccessService;
use Illuminate\Support\Collection;

class PriceChangeContext
{
    public function __construct(private BusinessLocationAccessService $access)
    {
    }

    public function businessId(): int
    {
        $businessId = (int) ($this->access->businessId() ?: 0);
        abort_if($businessId < 1, 403, 'A logged-in business could not be resolved.');

        return $businessId;
    }

    public function locations(): Collection
    {
        $options = BusinessLocation::forDropdown($this->businessId());
        $permittedLocationIds = $this->access->permittedLocationIds();

        // Preserve the assignment order stored for the logged-in user so the
        // first assigned location is the default location on the form.
        if (is_array($permittedLocationIds) && $permittedLocationIds !== []) {
            $position = array_flip(array_values(array_map('intval', $permittedLocationIds)));
            $options = $options->sortBy(
                fn ($name, $id): int => $position[(int) $id] ?? PHP_INT_MAX
            );
        }

        return $options
            ->map(fn ($name, $id) => (object) ['id' => (int) $id, 'name' => (string) $name])
            ->values();
    }

    public function isAdministrator(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $businessId = $this->businessId();

        return $user->can('superadmin')
            || ($businessId > 0 && $user->hasRole('Admin#' . $businessId));
    }

    /** @param array<int, string> $permissions */
    public function canAny(array $permissions): bool
    {
        $user = auth()->user();

        return $this->isAdministrator()
            || ($user && $user->canAny($permissions));
    }

    /** @param array<int, mixed> $locationIds
     *  @return array<int, int>
     */
    public function assertLocations(array $locationIds): array
    {
        $locationIds = array_values(array_unique(array_filter(array_map('intval', $locationIds))));
        abort_if($locationIds === [], 422, 'Select at least one business location.');

        $businessId = $this->businessId();
        foreach ($locationIds as $locationId) {
            $this->access->assertLocationAccess($locationId, $businessId);
        }

        return $locationIds;
    }
}
