<?php

namespace App\Http\Middleware;

use App\BusinessLocation;
use Closure;
use Illuminate\Support\Facades\Auth;

class UserLocationAccess
{
    public function handle($request, Closure $next)
    {
        if ($request->is('pumper-dashboard') || $request->is('pumper-dashboard/*')) {
            return $next($request);
        }

        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        $allowedLocations = $this->getUserAllowedLocations($request, $user);
        $this->setDefaultLocation($request, $allowedLocations);
        $this->validateLocationAccess($request, $allowedLocations);

        view()->share('allowedLocations', $allowedLocations);

        return $next($request);
    }

    private function getUserAllowedLocations($request, $user)
    {
        // Resolve once per request/session instead of querying on every middleware pass.
        $businessId = (int) $request->session()->get('user.business_id');
        $sessionKey = "erp_perf.allowed_locations.{$businessId}.{$user->id}";
        $cached = $request->session()->get($sessionKey);

        if (is_array($cached)) {
            return collect($cached)->map(static fn ($row) => (object) $row);
        }

        $locationIds = $user->permitted_locations();
        $query = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->select(['id', 'name', 'business_id']);

        if ($locationIds !== 'all') {
            if (! is_array($locationIds) || empty($locationIds)) {
                return collect();
            }
            $query->whereIn('id', $locationIds);
        }

        $locations = $query->orderBy('name')->get();
        $request->session()->put($sessionKey, $locations->map(static fn ($location) => [
            'id' => $location->id,
            'name' => $location->name,
            'business_id' => $location->business_id,
        ])->all());

        return $locations;
    }

    private function setDefaultLocation($request, $allowedLocations): void
    {
        $currentLocation = $request->session()->get('user.current_location');

        if ($currentLocation && ! $this->isLocationAllowed($currentLocation, $allowedLocations)) {
            $request->session()->forget('user.current_location');
            $currentLocation = null;
        }

        if (! $currentLocation && $allowedLocations->isNotEmpty()) {
            $request->session()->put('user.current_location', $allowedLocations->first()->id);
        }
    }

    private function validateLocationAccess($request, $allowedLocations): void
    {
        $requestedLocation = $request->get('location_id') ?? $request->route('location_id');
        if (! $requestedLocation || $requestedLocation === 'all') {
            return;
        }

        if (! $this->isLocationAllowed($requestedLocation, $allowedLocations)) {
            abort(403, 'Unauthorized access to this location.');
        }
    }

    private function isLocationAllowed($locationId, $allowedLocations): bool
    {
        return $allowedLocations->contains('id', (int) $locationId);
    }
}
