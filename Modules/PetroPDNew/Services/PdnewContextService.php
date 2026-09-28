<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PdnewContextService
{
    private bool $permittedLocationsResolved = false;

    /**
     * Null means the authenticated user may access every location in the
     * active business. An array means access is restricted to those IDs.
     *
     * @var array<int>|null
     */
    private ?array $permittedLocationIds = null;

    public function __construct(private ?Request $request = null) {}

    private function request(): Request
    {
        return $this->request ?? request();
    }

    public function businessId(): int
    {
        $session = $this->request()->session();
        $candidates = [
            $session->get('user.business_id'),
            $session->get('business.id'),
            $session->get('business_id'),
            optional($this->request()->user())->business_id,
        ];

        foreach ($candidates as $candidate) {
            if ((int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        throw new RuntimeException('No active business context was found for Petro PD-New.');
    }

    public function locationId(): ?int
    {
        $request = $this->request();
        $session = $request->session();
        $permitted = $this->permittedLocationIds();
        $requested = $request->input('location_id');

        // "All locations" is available only to a user with unrestricted
        // location access. Restricted users remain on their current permitted
        // location, or the first permitted location when none is selected.
        if ($requested === 'all' || $requested === 0 || $requested === '0') {
            if ($permitted === null) {
                return null;
            }

            return $this->restrictedFallbackLocation($permitted, $session);
        }

        if ((int) $requested > 0) {
            return $this->validateLocation((int) $requested, $permitted);
        }

        $candidates = [
            $session->get('user.current_location'),
            $session->get('business_location_id'),
            $session->get('location_id'),
            $session->get('user.location_id'),
        ];

        foreach ($candidates as $candidate) {
            if ((int) $candidate > 0) {
                return $this->validateLocation((int) $candidate, $permitted);
            }
        }

        if ($permitted !== null) {
            return $this->restrictedFallbackLocation($permitted, $session);
        }

        return null;
    }

    public function userId(): int
    {
        return (int) optional($this->request()->user())->id;
    }

    public function authorizeBusiness(int $businessId): void
    {
        abort_unless($businessId === $this->businessId(), 403, 'Cross-business Petro PD-New access is not permitted.');
    }

    public function authorizeLocation(?int $locationId): void
    {
        $activeLocationId = $this->locationId();

        if ($locationId === null || $locationId <= 0) {
            // Business-wide records (for example a consolidated Day End) are
            // available only in the explicit unrestricted/all-location scope.
            abort_unless(
                $activeLocationId === null,
                403,
                'Business-wide Petro PD-New records require all-location access.'
            );
            return;
        }

        if ($activeLocationId === null) {
            // Null is returned only for users with unrestricted location
            // access who deliberately selected the consolidated scope.
            $this->validateLocation($locationId, null);
            return;
        }

        abort_unless(
            $locationId === $activeLocationId,
            403,
            'Cross-location Petro PD-New access is not permitted for the active location.'
        );
    }

    /**
     * @return array<int>|null
     */
    private function permittedLocationIds(): ?array
    {
        if ($this->permittedLocationsResolved) {
            return $this->permittedLocationIds;
        }

        $this->permittedLocationsResolved = true;
        $user = $this->request()->user();

        if (! $user || ! method_exists($user, 'permitted_locations')) {
            // The application's authenticated User model provides this method.
            // Treat an unavailable method as no permitted locations rather than
            // accidentally exposing consolidated business data.
            $this->permittedLocationIds = [];
            return $this->permittedLocationIds;
        }

        $permitted = $user->permitted_locations();
        if ($permitted === 'all') {
            $this->permittedLocationIds = null;
            return null;
        }

        $this->permittedLocationIds = collect(is_array($permitted) ? $permitted : [])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        return $this->permittedLocationIds;
    }

    /**
     * @param array<int> $permitted
     */
    private function restrictedFallbackLocation(array $permitted, $session): int
    {
        abort_if($permitted === [], 403, 'No business location is assigned to this user.');

        $current = (int) $session->get('user.current_location');
        if ($current > 0 && in_array($current, $permitted, true)) {
            return $this->validateLocation($current, $permitted);
        }

        $locationId = $this->validateLocation((int) $permitted[0], $permitted);
        $session->put('user.current_location', $locationId);

        return $locationId;
    }

    /**
     * @param array<int>|null $permitted
     */
    private function validateLocation(int $locationId, ?array $permitted): int
    {
        abort_unless($locationId > 0, 403, 'A valid Petro PD-New location is required.');

        if ($permitted !== null) {
            abort_unless(
                in_array($locationId, $permitted, true),
                403,
                'Unauthorized access to this business location.'
            );
        }

        if (Schema::hasTable('business_locations')
            && Schema::hasColumn('business_locations', 'id')
            && Schema::hasColumn('business_locations', 'business_id')) {
            abort_unless(
                DB::table('business_locations')
                    ->where('id', $locationId)
                    ->where('business_id', $this->businessId())
                    ->exists(),
                403,
                'The selected location does not belong to the active business.'
            );
        }

        return $locationId;
    }
}
