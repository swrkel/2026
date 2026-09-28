<?php

namespace App\Services;

use App\Utils\SidebarPermissionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One authoritative business/location boundary for business-facing data.
 *
 * Normal users are restricted to the logged-in business and their assigned
 * locations. A Super Admin working inside a business remains restricted to
 * that business, while the central Super Admin area may explicitly request a
 * cross-business view.
 */
class BusinessLocationAccessService
{
    /** @var array<int, int>|null */
    private ?array $administrativeBusinessIds = null;

    public function businessId(?Request $request = null): ?int
    {
        $request = $request ?: request();

        // The authenticated user is the authoritative business boundary.
        // Session values can be stale after central Super Admin/business
        // switching, so they are fallbacks only when the authenticated user
        // genuinely has no business assigned.
        $value = optional(auth()->user())->business_id
            ?: $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id');

        return (int) $value ?: null;
    }

    public function isSuperAdmin(): bool
    {
        try {
            return auth()->check() && (bool) auth()->user()->can('superadmin');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isCentralSuperAdmin(): bool
    {
        try {
            return $this->isSuperAdmin()
                && ! SidebarPermissionUtil::isSuperAdminInsideBusiness();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Resolve every administrative business from the configured Super Admin
     * accounts. This deliberately does not assume that its ID is always 1.
     *
     * @return array<int, int>
     */
    public function administrativeBusinessIds(): array
    {
        if ($this->administrativeBusinessIds !== null) {
            return $this->administrativeBusinessIds;
        }

        if (! $this->isCentralSuperAdmin()) {
            return $this->administrativeBusinessIds = [];
        }

        try {
            $user = auth()->user();
            $userIds = array_filter([(int) optional($user)->id]);
            $businessIds = array_filter([(int) optional($user)->business_id]);
            $administratorUsernames = array_values(array_filter(array_map(
                'trim',
                explode(',', (string) config('constants.administrator_usernames', ''))
            )));

            if ($administratorUsernames) {
                $administrators = DB::table('users')
                    ->whereIn('username', $administratorUsernames)
                    ->get(['id', 'business_id']);

                foreach ($administrators as $administrator) {
                    $userIds[] = (int) $administrator->id;
                    $businessIds[] = (int) $administrator->business_id;
                }
            }

            $userIds = array_values(array_unique(array_filter($userIds)));
            if ($userIds) {
                $ownedBusinessIds = DB::table('business')
                    ->whereIn('owner_id', $userIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $businessIds = array_merge($businessIds, $ownedBusinessIds);
            }

            return $this->administrativeBusinessIds = array_values(array_unique(
                array_filter(array_map('intval', $businessIds))
            ));
        } catch (\Throwable $e) {
            return $this->administrativeBusinessIds = [];
        }
    }

    public function administrativeBusinessId(): ?int
    {
        $businessIds = $this->administrativeBusinessIds();
        $authenticatedBusinessId = (int) optional(auth()->user())->business_id;

        if ($authenticatedBusinessId > 0
            && in_array($authenticatedBusinessId, $businessIds, true)) {
            return $authenticatedBusinessId;
        }

        return $businessIds[0] ?? null;
    }

    /**
     * @return array<int, int>|string
     */
    public function permittedLocationIds()
    {
        // Super Admin may see every location, but cross-business access is
        // still granted only by applyScope(..., true) from a central page.
        if ($this->isSuperAdmin()) {
            return 'all';
        }

        if (! auth()->check()) {
            return [];
        }

        try {
            $locations = auth()->user()->permitted_locations();

            return $locations === 'all'
                ? 'all'
                : array_values(array_unique(array_filter(array_map('intval', (array) $locations))));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Apply both the business and user-location boundaries to an Eloquent or
     * query builder. Cross-business access must be explicitly requested and
     * is honoured only in the central Super Admin context.
     */
    public function applyScope(
        $query,
        string $businessColumn = 'business_id',
        string $locationColumn = 'location_id',
        ?int $businessId = null,
        bool $allowCentralSuperAdminAll = false
    ) {
        if ($allowCentralSuperAdminAll && $this->isCentralSuperAdmin()) {
            $administrativeBusinessIds = $this->administrativeBusinessIds();
            if (! $administrativeBusinessIds) {
                // Never expose the administrative location when its owning
                // business cannot be resolved safely.
                return $query->whereRaw('1 = 0');
            }

            return $query->whereNotIn($businessColumn, $administrativeBusinessIds);
        }

        $contextBusinessId = $this->businessId();
        if (! $contextBusinessId) {
            return $query->whereRaw('1 = 0');
        }

        // A caller-supplied business ID may narrow the current business but
        // must never switch the query to another business.
        if ($businessId && (int) $businessId !== $contextBusinessId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($businessColumn, $contextBusinessId);

        $permittedLocations = $this->permittedLocationIds();
        if ($permittedLocations !== 'all') {
            $query->whereIn($locationColumn, $permittedLocations ?: [-1]);
        }

        return $query;
    }

    public function applyLocationScope($query, string $locationColumn = 'location_id')
    {
        $permittedLocations = $this->permittedLocationIds();
        if ($permittedLocations !== 'all') {
            $query->whereIn($locationColumn, $permittedLocations ?: [-1]);
        }

        return $query;
    }

    public function canAccessLocation(?int $locationId, ?int $businessId = null): bool
    {
        if (! $locationId) {
            return false;
        }

        $contextBusinessId = $this->businessId();
        if (! $contextBusinessId
            || ($businessId && (int) $businessId !== $contextBusinessId)) {
            return false;
        }

        $belongsToBusiness = DB::table('business_locations')
            ->where('id', $locationId)
            ->where('business_id', $contextBusinessId)
            ->exists();

        if (! $belongsToBusiness) {
            return false;
        }

        $permittedLocations = $this->permittedLocationIds();

        return $permittedLocations === 'all'
            || in_array($locationId, $permittedLocations, true);
    }

    public function assertLocationAccess(?int $locationId, ?int $businessId = null): void
    {
        abort_unless(
            $this->canAccessLocation($locationId, $businessId),
            403,
            'The selected location is not available for this business or user.'
        );
    }

    public function locationOptions(?int $businessId = null): Collection
    {
        $query = DB::table('business_locations')
            ->select('id', 'business_id', 'name', 'location_id')
            ->orderBy('name');

        $this->applyScope(
            $query,
            'business_locations.business_id',
            'business_locations.id',
            $businessId
        );

        return $query->get();
    }
}
