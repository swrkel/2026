<?php

namespace Modules\SimpleAudit\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class AccessService
{
    protected $tenants;

    public function __construct(TenantConnectionManager $tenants)
    {
        $this->tenants = $tenants;
    }

    public function isSuperAdmin($user = null)
    {
        $user = $user ?: auth()->user();
        if (!$user) return false;

        // The current ERP uses the `superadmin` permission as the canonical
        // signed Super Admin identity. Check it before legacy flag/role fallbacks.
        if (method_exists($user, 'can')) {
            try {
                if ((bool) $user->can('superadmin')) return true;
            } catch (\Throwable $e) {
                // Continue with dependency-free legacy checks.
            }
        }

        foreach (['is_superadmin','is_super_admin','is_superadmin_default','superadmin'] as $field) {
            if (isset($user->{$field}) && (int) $user->{$field} === 1) return true;
        }

        if (method_exists($user, 'hasRole')) {
            try {
                if ($user->hasRole('Super Admin') || $user->hasRole('SuperAdmin') || $user->hasRole('superadmin')) return true;
            } catch (\Throwable $e) {
                // No dependency on the host role package.
            }
        }
        return false;
    }


    /**
     * Simple Audit is a Central Super Admin module only.
     * Tenant/business users must never see or open the audit console itself.
     */
    public function isCentralRequest(): bool
    {
        try {
            $host = strtolower(trim((string) request()->getHost()));
            $host = preg_replace('/:\\d+$/', '', $host);

            $centralDomains = [];
            foreach ((array) config('tenancy.central_domains', []) as $domain) {
                $domain = strtolower(trim((string) $domain));
                $domain = preg_replace('#^https?://#', '', $domain);
                $domain = preg_replace('/:\\d+$/', '', $domain);
                $domain = trim($domain, '/ ');
                if ($domain !== '') {
                    $centralDomains[] = $domain;
                }
            }
            $centralDomains = array_values(array_unique($centralDomains));

            if ($centralDomains !== []) {
                return in_array($host, $centralDomains, true);
            }

            // Safe fallback for installations where central_domains is empty.
            if (function_exists('tenancy')) {
                try {
                    return ! (bool) tenancy()->initialized;
                } catch (\Throwable $e) {
                    // Continue with the conservative fallback below.
                }
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isCentralAccessAllowed($user = null): bool
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return false;
        }

        /*
         * IMPORTANT: the host application's canonical distinction between the
         * genuine Central Super Admin session and "Login As Business" is
         * SidebarPermissionUtil::isGenuineSuperAdmin(). Do not reject that
         * session merely because tenancy.central_domains is stale, incomplete,
         * dynamically rewritten, or this installation keeps central businesses
         * in the same physical database.
         *
         * This also preserves the central-only rule: Login As Business keeps
         * the superadmin permission, but isGenuineSuperAdmin() deliberately
         * returns false while the signed impersonation context is active.
         */
        if (class_exists('\App\Utils\SidebarPermissionUtil')) {
            try {
                if (\App\Utils\SidebarPermissionUtil::isGenuineSuperAdmin()) {
                    return true;
                }

                if (method_exists('\App\Utils\SidebarPermissionUtil', 'isSuperAdminInsideBusiness')
                    && \App\Utils\SidebarPermissionUtil::isSuperAdminInsideBusiness()) {
                    return false;
                }
            } catch (\Throwable $e) {
                // Fall back to the module-owned central-host check below.
            }
        }

        // Compatibility fallback for installations without the current host
        // helper. Here both a central request and Super Admin identity are
        // required, so ordinary tenant/business users remain blocked.
        return $this->isCentralRequest() && $this->isSuperAdmin($user);
    }

    public function assertCentralAccess(): bool
    {
        if (!$this->isCentralAccessAllowed()) {
            throw new AuthorizationException('Simple Audit is available only from the Central Super Admin system.');
        }

        return true;
    }

    public function assertTenant($tenantId)
    {
        $user = auth()->user();
        if (!$user) throw new AuthorizationException(__('simpleaudit::simpleaudit.authentication_required'));
        if ($this->isSuperAdmin($user) || !$tenantId) return true;
        $currentTenant = $this->tenants->currentTenantIdFromHost();
        if (!$currentTenant || (string)$tenantId !== (string)$currentTenant) {
            throw new AuthorizationException(__('simpleaudit::simpleaudit.tenant_access_denied'));
        }
        return true;
    }

    public function assertScope($tenantId, $businessId, $locationId = null)
    {
        $user = auth()->user();
        if (!$user) throw new AuthorizationException(__('simpleaudit::simpleaudit.authentication_required'));
        if ($this->isSuperAdmin($user)) return true;

        $currentTenant = $this->tenants->currentTenantIdFromHost();
        if ($tenantId && $currentTenant && (string)$tenantId !== (string)$currentTenant) {
            throw new AuthorizationException(__('simpleaudit::simpleaudit.tenant_access_denied'));
        }

        if (isset($user->business_id) && $user->business_id && (int)$user->business_id !== (int)$businessId) {
            throw new AuthorizationException(__('simpleaudit::simpleaudit.business_access_denied'));
        }

        if ($locationId && isset($user->location_permissions) && $user->location_permissions) {
            $allowed = json_decode($user->location_permissions, true);
            if (is_array($allowed) && $allowed && !in_array((int)$locationId, array_map('intval', $allowed), true)) {
                throw new AuthorizationException(__('simpleaudit::simpleaudit.location_access_denied'));
            }
        }
        return true;
    }


    public function assertPermission($key)
    {
        $user = auth()->user();
        if (!$user) {
            throw new AuthorizationException(__('simpleaudit::simpleaudit.authentication_required'));
        }
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $permission = config('simpleaudit.permissions.' . $key);
        if (!$permission) {
            return true;
        }

        // Spatie-compatible permission check when the host uses that package.
        if (method_exists($user, 'hasPermissionTo')) {
            try {
                if (!$user->hasPermissionTo($permission)) {
                    throw new AuthorizationException(__('simpleaudit::simpleaudit.permission_denied'));
                }
                return true;
            } catch (AuthorizationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // Permission may not have been registered yet. Fall through to Gate.
            }
        }

        try {
            if (Gate::has($permission)) {
                if (!Gate::forUser($user)->allows($permission)) {
                    throw new AuthorizationException(__('simpleaudit::simpleaudit.permission_denied'));
                }
                return true;
            }
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Keep the module independent when the host has no permission backend.
        }

        return true;
    }

    public function filterBusinesses(array $businesses)
    {
        $user = auth()->user();
        if (!$user || $this->isSuperAdmin($user) || empty($user->business_id)) return $businesses;
        return array_values(array_filter($businesses, function ($row) use ($user) {
            return (int)$row['id'] === (int)$user->business_id;
        }));
    }

    public function filterLocations(array $locations)
    {
        $user = auth()->user();
        if (!$user || $this->isSuperAdmin($user) || empty($user->location_permissions)) return $locations;
        $allowed = json_decode($user->location_permissions, true);
        if (!is_array($allowed) || !$allowed) return $locations;
        $ids = array_map('intval', $allowed);
        return array_values(array_filter($locations, fn($row) => in_array((int)$row['id'], $ids, true)));
    }
}
