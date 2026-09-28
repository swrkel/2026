<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Superadmin\Entities\Subscription;
use Modules\Customers\Services\CustomerModulePageRegistry;

/**
 * Customers-owned permission and subscription resolver.
 *
 * Security standard used by the standalone Customers module:
 * 1) Business subscription/package must allow the module.
 * 2) Logged-in user must have the required role permission.
 * 3) Sidebar and routes use the same resolver, so hidden menus and direct URLs stay aligned.
 */
class CustomerPermissionService
{
    protected array $packageCache = [];
    protected array $moduleEnabledCache = [];
    protected array $abilityCache = [];
    /**
     * Customers-owned permission names.
     *
     * CUS_FINAL_001 cleanup: the primary permission map no longer hardcodes
     * Contact module permissions. Legacy Contact/Customer permission fallbacks
     * are appended only when config('customers.keep_legacy_contact_permission_fallbacks')
     * is true. This keeps production safe while allowing a clean standalone cutover.
     */
    protected $permissionMap = [
        'access'    => ['customers.access', 'customers.dashboard', 'customers.view'],
        'dashboard' => ['customers.dashboard', 'customers.access', 'customers.view'],
        'view'      => ['customers.view', 'customers.access'],
        'create'    => ['customers.create'],
        'edit'      => ['customers.edit', 'customers.update'],
        'delete'    => ['customers.delete'],
        'reports'   => ['customers.reports', 'customers.access'],
        'export'    => ['customers.export', 'customers.reports'],
        'settings'  => ['customers.settings', 'customers.access'],
        'portal'    => ['customers.portal', 'customers.portal.login', 'customers.access'],
        'approve'   => ['customers.approve', 'customers.workflow', 'customers.edit'],
        'credit'    => ['customers.credit', 'customers.balance', 'customers.edit'],
        'audit'     => ['customers.audit', 'customers.activity', 'customers.view'],
        'documents' => ['customers.documents', 'customers.notes', 'customers.view'],
        'payment'   => ['customers.payment', 'customers.advance_payment', 'customers.deposit', 'customers.refund', 'customers.cheque_return', 'customers.edit'],
    ];

    /**
     * Temporary fallback map for tenants not yet migrated to customers.* permissions.
     */
    protected $legacyPermissionFallbacks = [
        'access'    => ['customer.view', 'contact.view'],
        'dashboard' => ['customer.view', 'contact.view'],
        'view'      => ['customer.view', 'contact.view'],
        'create'    => ['customer.create', 'contact.create'],
        'edit'      => ['customer.update', 'contact.update'],
        'delete'    => ['customer.delete', 'contact.delete'],
        'reports'   => ['customer.view', 'contact.view'],
        'export'    => ['customer.view', 'contact.view'],
        // Existing tenants commonly use the legacy update permission for
        // customer receipts. Keep Bulk Payment available during the standalone
        // customers.* permission migration without granting it to read-only roles.
        'payment'   => ['customer.update', 'contact.update'],
    ];

    public function authorize(string $ability): void
    {
        if (! $this->moduleEnabled()) {
            abort(403, 'Customers module is not enabled for this business.');
        }

        if (! $this->allows($ability)) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Returns true only when the Customers module is enabled for the current business.
     *
     * Backward compatibility:
     * - If the new package key customers_module exists, it is the source of truth.
     * - If the key does not exist yet in older packages, legacy customer/contact flags are accepted.
     */

    /**
     * Safely return current business package details as an array.
     */
    public function packageDetails(?int $businessId = null): array
    {
        $businessId = $businessId
            ?: (int) (session('user.business_id') ?: session('business.id') ?: optional(Auth::user())->business_id);

        if (empty($businessId)) {
            return [];
        }

        if (array_key_exists($businessId, $this->packageCache)) {
            return $this->packageCache[$businessId];
        }

        try {
            $subscription = Subscription::current_subscription($businessId);
            return $this->packageCache[$businessId] = (! empty($subscription)
                ? (array) $subscription->package_details
                : []);
        } catch (\Throwable $e) {
            return $this->packageCache[$businessId] = [];
        }
    }

    /**
     * Returns true when a Customers Module individual page switch is enabled
     * for the current business package. Used by the system/sidebar menu.
     */
    public function pageEnabled(string $pageKey, ?int $businessId = null): bool
    {
        if (! $this->moduleEnabled($businessId)) {
            return false;
        }

        $package = $this->packageDetails($businessId);

        if (! empty($package[$pageKey])) {
            return true;
        }

        $page = CustomerModulePageRegistry::pages()[$pageKey] ?? null;
        if (empty($page)) {
            return false;
        }

        // Existing packages were saved before the page registry had a version
        // marker, and the former duplicate Manage form could write an automatic
        // zero even when the page was never deliberately disabled. Apply the
        // default only to those legacy packages. Once saved with the configured
        // marker, an unchecked switch is respected normally.
        if (empty($package[CustomerModulePageRegistry::CONFIGURED_KEY])) {
            return ! empty($page['default_enabled']);
        }

        return false;
    }

    public function enabledPageKeys(?int $businessId = null): array
    {
        return array_values(array_filter(CustomerModulePageRegistry::keys(), function ($key) use ($businessId) {
            return $this->pageEnabled($key, $businessId);
        }));
    }

    public function moduleEnabled(?int $businessId = null): bool
    {
        $businessId = $businessId
            ?: (int) (session('user.business_id') ?: session('business.id') ?: optional(Auth::user())->business_id);

        if (empty($businessId)) {
            return false;
        }

        if (array_key_exists($businessId, $this->moduleEnabledCache)) {
            return $this->moduleEnabledCache[$businessId];
        }

        $package = $this->packageDetails($businessId);

        if (array_key_exists('customers_module', $package)) {
            return $this->moduleEnabledCache[$businessId] = ! empty($package['customers_module']);
        }

        // Legacy fallback for tenants where the new Customers package flag has not yet been added.
        foreach (['customer_module', 'contact_customer', 'contact_module', 'customer_statement', 'customer_payment'] as $legacyKey) {
            if (array_key_exists($legacyKey, $package) && ! empty($package[$legacyKey])) {
                return $this->moduleEnabledCache[$businessId] = true;
            }
        }

        // Superadmin is allowed only if the package did not explicitly disable customers_module.
        $user = Auth::user();
        if ($user && method_exists($user, 'can') && $user->can('superadmin')) {
            return $this->moduleEnabledCache[$businessId] = true;
        }

        return $this->moduleEnabledCache[$businessId] = false;
    }

    public function allows(string $ability): bool
    {
        if (array_key_exists($ability, $this->abilityCache)) {
            return $this->abilityCache[$ability];
        }

        $user = Auth::user();

        if (empty($user)) {
            return $this->abilityCache[$ability] = false;
        }

        if (! $this->moduleEnabled()) {
            return $this->abilityCache[$ability] = false;
        }

        // Superadmin/Admin users may access only when module subscription is enabled.
        foreach (['superadmin', 'Admin#' . (session('user.business_id') ?: session('business.id')), 'admin'] as $roleName) {
            try {
                if (method_exists($user, 'hasRole') && $user->hasRole($roleName)) {
                    return $this->abilityCache[$ability] = true;
                }
            } catch (\Throwable $e) {
                // Ignore role provider issues; fall through to can().
            }
        }

        $permissions = $this->permissionsFor($ability);

        foreach ($permissions as $permission) {
            try {
                if (method_exists($user, 'can') && $user->can($permission)) {
                    return $this->abilityCache[$ability] = true;
                }
            } catch (\Throwable $e) {
                // Missing permission names should never crash the Customers UI.
            }
        }

        return $this->abilityCache[$ability] = false;
    }

    public function permissionsFor(string $ability): array
    {
        $permissions = $this->permissionMap[$ability] ?? [$ability];

        if ((bool) config('customers.keep_legacy_contact_permission_fallbacks', true)) {
            $permissions = array_merge($permissions, $this->legacyPermissionFallbacks[$ability] ?? []);
        }

        return array_values(array_unique($permissions));
    }

    public function allKnownPermissions(): array
    {
        $permissions = [];

        foreach (array_keys($this->permissionMap) as $ability) {
            $permissions[$ability] = $this->permissionsFor($ability);
        }

        return $permissions;
    }
}
