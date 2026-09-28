<?php

namespace Modules\ProductsNew\Utilities;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use RuntimeException;

class ProductsNewTenantGuard
{
    /**
     * Resolve and validate the current business id.
     *
     * This method is intentionally static because both older injected services
     * and newer Products New services call the guard statically. PHP permits a
     * static method to be invoked through an injected instance as well.
     */
    public static function businessId(?int $requestedBusinessId = null): int
    {
        $userBusinessId = optional(Auth::user())->business_id;
        $sessionBusinessId = session('user.business_id');

        // The authenticated identity is authoritative. A historical/stale
        // session value must never switch Products New to another business
        // (especially the central Super Admin business).
        $businessId = $userBusinessId ?: $sessionBusinessId;

        if (empty($businessId)) {
            throw new RuntimeException('Products New could not resolve the active business.');
        }

        $businessId = (int) $businessId;
        if ($requestedBusinessId !== null && (int) $requestedBusinessId !== $businessId) {
            throw new AccessDeniedHttpException('The requested business is not available in this session.');
        }

        if ($sessionBusinessId && $userBusinessId != $sessionBusinessId) { \Illuminate\Support\Facades\Auth::setUser(\App\User::find(optional(\Illuminate\Support\Facades\Auth::user())->id)); $userBusinessId = optional(\Illuminate\Support\Facades\Auth::user())->business_id; }
        return $businessId;
    }

    public static function ensureBusinessId(Request $request): int
    {
        $requested = $request->input('business_id');
        $businessId = self::businessId($requested !== null ? (int) $requested : null);

        // Keep controllers/services that read from the request compatible.
        $request->merge(['business_id' => $businessId]);

        return $businessId;
    }

    public static function userId(): ?int
    {
        $id = optional(Auth::user())->id;
        return $id !== null ? (int) $id : null;
    }

    public static function allowedLocationIds(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        if (method_exists($user, 'permitted_locations')) {
            $locations = $user->permitted_locations();
            return $locations === 'all'
                ? ['all']
                : array_values(array_filter((array) $locations, static fn ($id) => $id !== null && $id !== ''));
        }

        return ['all'];
    }

    public static function applyBusiness($query, ?string $column = 'business_id')
    {
        if ($column) {
            $query->where($column, self::businessId());
        }

        return $query;
    }
}
