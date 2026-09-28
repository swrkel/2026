<?php

namespace Modules\PetroDirectNew\Support;

use Illuminate\Support\Facades\Auth;

class BusinessContext
{
    public function businessId(): int
    {
        return (int) (session('user.business_id') ?: session('business.id') ?: 0);
    }

    public function userId(): int
    {
        return (int) (Auth::id() ?: 0);
    }

    public function permittedLocationIds(): array
    {
        try {
            if (function_exists('permitted_locations')) {
                $locations = permitted_locations();
                if ($locations === 'all') {
                    return [];
                }
                return array_values(array_filter(array_map('intval', (array) $locations)));
            }
        } catch (\Throwable $e) {
        }

        return [];
    }

    public function locationAllowed(?int $locationId): bool
    {
        if (!$locationId) {
            return true;
        }
        $permitted = $this->permittedLocationIds();
        return $permitted === [] || in_array((int) $locationId, $permitted, true);
    }

    public function requireBusiness(): int
    {
        $businessId = $this->businessId();
        abort_if($businessId < 1, 403, 'Business context is required.');
        return $businessId;
    }
}
