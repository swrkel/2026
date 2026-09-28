<?php

namespace Modules\Loan\Services;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LoanLocationScopeService
{
    /**
     * Return location IDs allowed for the logged-in user.
     * Empty array means head-office/all-location access, matching the existing Loan module logic.
     */
    public function allowedLocationIds(?User $user): array
    {
        if (!$user || empty($user->location_permissions)) {
            return [];
        }

        $decoded = json_decode($user->location_permissions, true);

        return is_array($decoded)
            ? array_values(array_filter($decoded))
            : [];
    }

    public function isHeadOfficeUser(?User $user): bool
    {
        return empty($this->allowedLocationIds($user));
    }

    public function applyToQuery(Builder $query, ?User $user, string $column = 'location_id'): Builder
    {
        $allowed = $this->allowedLocationIds($user);

        if (!empty($allowed)) {
            $query->whereIn($column, $allowed);
        }

        return $query;
    }

    public function userCanAccessLocation(?User $user, $locationId): bool
    {
        $allowed = $this->allowedLocationIds($user);

        return empty($allowed) || in_array($locationId, $allowed);
    }

    public function locationsForBusiness(int $businessId, ?User $user)
    {
        $query = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->orderBy('name');

        $allowed = $this->allowedLocationIds($user);

        if (!empty($allowed)) {
            $query->whereIn('id', $allowed);
        }

        return $query->pluck('name', 'id');
    }
}
