<?php

namespace App\Utils;

use App\Business;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\AutomaticModuleRegistry;
use App\Services\Authorization\SuperAdminImpersonation;
use App\Services\GlobalSchemaCache;

class SidebarPermissionUtil
{
    // SIDEBAR-IDENTITY-V8: central business resolution uses UID or safe tenant/company mapping.
    private static array $requestRows = [];

    /**
     * Request-local mapping from the current database's business id to the
     * matching CENTRAL business identity. Numeric business ids are database-
     * local, so this cache stores only identities resolved by global_uid or by
     * a tenant-scoped company-number compatibility match.
     *
     * @var array<int, array<string, mixed>|null>
     */
    private static array $requestCentralBusinessIdentities = [];


    private static ?bool $requestTableExists = null;

    /** @var array<string, bool> */
    private static array $requestEnabledStates = [];

    /** @var array<string, array<int, string>> */
    private static array $requestAliases = [];

    /** @var array<int, array<string, int>|null> */
    private static array $requestPackageBlueprintScopes = [];

    /** @var array<int, array<string, mixed>|null> */
    private static array $requestSubscriptionPackageDetails = [];

    /** @var array<int, array<int, string>> */
    private static array $requestExplicitDisabledKeys = [];

    /** @var array<int, array<int, string>> */
    private static array $requestDisabledSidebarVariables = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    private static array $requestDisabledSidebarDescriptors = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    private static array $requestDisabledAutomaticPermissionDescriptors = [];

    /** @var array<string, bool> */
    private static array $requestManagedRoleModuleStates = [];

    /** @var array<string, bool>|null */
    private static ?array $requestManageNewPermissionKeys = null;

    /**
     * Exact UserManagementNew permission contract attached to the assigned
     * business role. A null value means the user is not managed by that
     * standalone role system and must retain legacy behaviour.
     *
     * @var array<string, array<string, bool>|null>
     */
    private static array $requestManagedRolePermissionSets = [];

    /**
     * True only while a Super Admin is operating inside a business/tenant UI.
     *
     * This is intentionally narrower than hasSuperAdminBypass(): central
     * Super Admin pages keep their own module menu, while tenant/business pages
     * hide that menu but retain full functional access.
     */
    public static function isSuperAdminInsideBusiness(): bool
    {
        try {
            if (! auth()->check()
                || ! class_exists(SuperAdminImpersonation::class)) {
                return false;
            }

            // Never trust the historical loose session flags here. A tenant
            // bypass is valid only when the signed context still matches the
            // authenticated user, target business and current session id.
            return SuperAdminImpersonation::isActive(auth()->user(), request());
        } catch (\Throwable $e) {
            // Authorization bypasses must fail closed, but sidebar rendering
            // must not cause a login 500 during a staggered deployment.
            return false;
        }
    }

    /**
     * The Super Admin navigation belongs only to the central Super Admin UI.
     *
     * Login As Business deliberately retains the Super Admin permission for
     * functional support access, so permission alone is not a sufficient menu
     * visibility check.
     */
    public static function shouldShowSuperAdminMenu(): bool
    {
        return self::isGenuineSuperAdmin();
    }

    /**
     * True only for the genuine central Super Admin session.
     *
     * Do not use business_id === 1 as an identity check. Installations can move
     * the central business record, while Login As Business deliberately keeps a
     * signed impersonation context. The authenticated superadmin permission plus
     * the absence of that signed impersonation context is the stable distinction.
     */
    public static function isGenuineSuperAdmin(): bool
    {
        try {
            return auth()->check()
                && (bool) auth()->user()->can('superadmin')
                && ! self::isSuperAdminInsideBusiness();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * A genuine Super Admin, including a Super Admin impersonating a tenant,
     * must not be restricted by business sidebar/package/page settings.
     */
    public static function hasSuperAdminBypass(): bool
    {
        try {
            if (! auth()->check()) {
                return false;
            }

            if (self::isSuperAdminInsideBusiness()) {
                return true;
            }

            return (bool) auth()->user()->can('superadmin');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Core ERP sidebar sections that are not represented by their own module
     * folder, or that must remain distinct from a similarly named standalone
     * module. The canonical key is the value saved by Manage Side Bar.
     *
     * @return array<string, array{title:string,aliases:array<int,string>,route_prefixes:array<int,string>,package_aliases?:array<int,string>}>
     */
    public static function coreSidebarDefinitions(): array
    {
        return [
            'accounting_module' => [
                'title' => 'Accounting Module',
                'aliases' => ['accounting_module', 'access_account', 'account', 'accounts', 'accounting'],
                'route_prefixes' => ['accounting-module', 'account', 'accounts', 'journal-entry', 'fixed-assets'],
                'package_aliases' => ['access_account'],
            ],
            'contact_module' => [
                'title' => 'Contact Module',
                'aliases' => ['contact_module', 'contact', 'contacts'],
                'route_prefixes' => ['contacts', 'contact', 'contact-group', 'import-contacts', 'customer-reference', 'customer-statement', 'customer-payment', 'outstanding-received', 'issue-payment-detail'],
            ],
            'purchases' => [
                'title' => 'Purchases (Core)',
                'aliases' => ['purchases', 'purchases_module', 'core_purchases'],
                'route_prefixes' => ['purchases', 'purchase-return', 'purchase-pos', 'import-purchases'],
                'package_aliases' => ['purchase'],
            ],
            'expenses' => [
                'title' => 'Expenses (Core)',
                'aliases' => ['expenses', 'expenses_module', 'core_expenses'],
                'route_prefixes' => ['expenses', 'expense-categories', 'expense-categories-code', 'expense-category-codes', 'expense-categories-number', 'expense', 'save_add_expense_data', 'get-expense-account-category-id'],
            ],
            'products' => [
                'title' => 'Products (Core)',
                'aliases' => ['products', 'products_module', 'core_products'],
                'route_prefixes' => ['products', 'import-products', 'variation-templates', 'selling-price-group', 'warranties', 'units', 'brands', 'categories'],
            ],
            'stock_transfer' => [
                'title' => 'Stock Transfer (Core)',
                'aliases' => ['stock_transfer', 'stock_transfers'],
                'route_prefixes' => ['stock-transfers', 'stock-transfers-request'],
            ],
            'stock_adjustment' => [
                'title' => 'Stock Adjustment (Core)',
                'aliases' => ['stock_adjustment', 'stock_adjustments'],
                'route_prefixes' => ['stock-adjustments', 'stock-settings'],
            ],
            'sales' => [
                'title' => 'Sales',
                'aliases' => ['sales', 'sale_module'],
                'route_prefixes' => ['sells', 'sales', 'pos', 'sell-return', 'quotations', 'drafts', 'shipments', 'discount', 'import-sales', 'reserved-stocks'],
                'package_aliases' => ['sale_module'],
            ],
            'reports' => [
                'title' => 'Reports',
                'aliases' => ['reports', 'report_module'],
                'route_prefixes' => ['reports'],
                'package_aliases' => ['report_module'],
            ],
            'settings' => [
                'title' => 'Settings',
                'aliases' => ['settings', 'settings_module'],
                'route_prefixes' => ['business/settings', 'business/update', 'business/dayEnd', 'business-location', 'invoice-schemes', 'invoice-layouts', 'printers', 'tax-rates', 'barcode-settings'],
                'package_aliases' => ['settings_module'],
            ],
            'user_management' => [
                'title' => 'User Management',
                'aliases' => ['user_management', 'user_management_module'],
                'route_prefixes' => ['users', 'user', 'roles', 'sales-commission-agents'],
                'package_aliases' => ['user_management_module'],
            ],
            'pumper_dashboard' => [
                'title' => 'Pumper Dashboard',
                'aliases' => ['pumper_dashboard', 'pump_operator_dashboard'],
                'route_prefixes' => ['pumper-dashboard', 'pump-operator', 'pumper'],
                'package_aliases' => ['pump_operator_dashboard'],
            ],
            // Help Guide is a shared system utility.  It is represented here so
            // S717's Manage Side Bar bridge does not fall back to the retiring
            // package/Manage-page flag simply because Help Guide is not a core
            // ERP module folder.  Existing `helpguide` installations therefore
            // default visible until explicitly disabled in Manage Side Bar.
            'help_guide' => [
                'title' => 'Help Guide',
                'aliases' => ['help_guide', 'helpguide', 'help_guide_module', 'helpguide_module'],
                'route_prefixes' => ['help-guide', 'helpguide'],
                'package_aliases' => ['helpguide'],
            ],
        ];
    }

    public static function tableExists(): bool
    {
        if (self::$requestTableExists !== null) {
            return self::$requestTableExists;
        }

        // Manage Side Bar is saved from the central Super Admin area. Tenant
        // requests must therefore read the central business row instead of a
        // tenant-local copy that can be stale after a deployment or clone.
        try {
            $connection = self::centralConnectionName();

            return self::$requestTableExists = Schema::connection($connection)->hasTable('business')
                && Schema::connection($connection)->hasColumn('business', 'enabled_modules');
        } catch (\Throwable $e) {
            // Rolling-deployment fallback for installations without the named
            // central connection yet. Never break the sidebar during fallback.
            try {
                return self::$requestTableExists = GlobalSchemaCache::hasTable('business')
                    && GlobalSchemaCache::hasColumn('business', 'enabled_modules');
            } catch (\Throwable $fallbackException) {
                return self::$requestTableExists = false;
            }
        }
    }

    /**
     * Functional module access.
     *
     * Central Super Admin keeps full access. Login As Business adopts the
     * selected business policy so module checks match its sidebar and URLs.
     */
    public static function isEnabled(?string $moduleKey, ?int $businessId = null): bool
    {
        // Central Super Admin is unrestricted. Login As Business must enforce
        // the selected business's saved page permissions just like its sidebar.
        if (self::isGenuineSuperAdmin()) {
            return true;
        }

        return self::resolveConfiguredState($moduleKey, $businessId);
    }

    /**
     * Authoritative state of the parent switch saved by Manage Side Bar.
     *
     * This intentionally checks only the per-business Manage Side Bar value.
     * Package and page/role permissions are separate child gates and must not
     * turn an enabled parent into a misleading "disabled from Manage Side Bar"
     * response.
     */
    /**
     * A newer module standing in for the one it replaces.
     *
     * Stock Adjustment New replaces Stock Adjustment (Core). A business that
     * has moved switches the core module OFF and ticks the new one.
     *
     * But EnforceBusinessSidebarModuleAccess checks every module key a request
     * resolves to, and features that create a stock adjustment still resolve
     * to the core key. Petro General's Dip Resetting is one: it writes a
     * stock_adjustment transaction internally, so the save was refused with
     * "Blocked by the setting: stock_adjustment" even though the business had
     * deliberately moved to the new module.
     *
     * Without this, every feature touching a superseded module fails once that
     * module is switched off - each looking like a separate bug.
     *
     * One-directional: the NEW module satisfies a check for the OLD one, never
     * the reverse. Switching off the new module still blocks, which is what a
     * business turning the feature off would expect.
     *
     * Add a line as each further module is retired.
     */
    private static function supersededBy(string $moduleKey): array
    {
        $map = [
            'stock_adjustment' => ['stock_adjustment_new', 'stockadjustmentnew'],
            'stock_transfer'   => ['stock_transfer_new', 'stocktransfernew'],
            'stocktaking'      => ['stock_taking_new', 'stocktakingnew'],
        ];

        return $map[self::normalizeKey($moduleKey)] ?? [];
    }

    public static function isManageSidebarEnabled(?string $moduleKey, ?int $businessId = null): bool
    {
        /*
         | A module that replaces this one counts as satisfying it. See
         | supersededBy() above.
         */
        foreach (self::supersededBy((string) $moduleKey) as $replacement) {
            if (self::resolveConfiguredState($replacement, $businessId)) {
                return true;
            }
        }

        if (empty($moduleKey)) {
            return true;
        }

        // The middleware is business-session scoped. `business.id` can still
        // contain the central business during Login As Business, while
        // `user.business_id` is the selected tenant enforced by this guard.
        $businessId = $businessId ?: (int) session('user.business_id');
        if (empty($businessId) || ! self::tableExists()) {
            return true;
        }

        $canonical = self::canonicalModuleKey($moduleKey);
        if ($canonical === '') {
            return true;
        }

        if (AutomaticModuleRegistry::standaloneGlobalAvailability($canonical) === false) {
            return false;
        }

        $rows = self::cachedRows($businessId);
        foreach (self::aliasesFor($canonical) as $key) {
            if (array_key_exists($key, $rows) && (int) $rows[$key] === 0) {
                return false;
            }
        }

        foreach (self::aliasesFor($canonical) as $key) {
            if (array_key_exists($key, $rows) && (int) $rows[$key] === 1) {
                return true;
            }
        }

        // New installed modules default to visible until the business
        // explicitly unchecks them, matching the Manage Side Bar UI contract.
        return AutomaticModuleRegistry::findSidebar($canonical) !== null
            || array_key_exists($canonical, self::coreSidebarDefinitions())
            || $rows === [];
    }

    /**
     * Resolve a subscription-style permission name to a Manage Side Bar parent.
     *
     * Many older modules still call ModuleUtil::hasThePermissionInSubscription()
     * with names such as `petro_pd_module`.  Those calls historically made the
     * retiring Manage page an accidental second parent-module authority.  This
     * helper identifies only true parent aliases; child/page permissions such as
     * `petro_pd_pd_settlement` are deliberately not matched and remain under
     * Manage New.
     */
    public static function manageSidebarParentKeyForPermission(
        ?string $permission,
        ?int $businessId = null
    ): ?string {
        $normal = self::normalizeKey((string) $permission);
        if ($normal === '') {
            return null;
        }

        $canonical = self::canonicalModuleKey($normal);
        if ($canonical === '') {
            return null;
        }

        // Exact parent aliases only.  Do not let a child permission be promoted
        // to the whole module simply because it contains the module name.
        if (! in_array($normal, self::aliasesFor($canonical), true)) {
            return null;
        }

        $knownParent = AutomaticModuleRegistry::findSidebar($canonical) !== null
            || array_key_exists($canonical, self::coreSidebarDefinitions());

        // Historical/static Manage Side Bar switches are not always represented
        // by a module.json entry.  If this business actually stores the exact
        // alias in enabled_modules, it is still a valid parent switch.
        if (! $knownParent) {
            $businessId = $businessId ?: (int) session('user.business_id');
            if ($businessId > 0 && self::tableExists()) {
                $rows = self::cachedRows($businessId);
                foreach (self::aliasesFor($canonical) as $alias) {
                    if (array_key_exists($alias, $rows)) {
                        $knownParent = true;
                        break;
                    }
                }
            }
        }

        return $knownParent ? $canonical : null;
    }

    /**
     * Authoritative business sidebar visibility.
     *
     * This deliberately does NOT apply the Super Admin functional-access bypass.
     * A Super Admin who used "Login As Business" can still open every tenant URL,
     * but the tenant sidebar shows only the modules checked for that business.
     */
    public static function isVisibleInSidebar(?string $moduleKey, ?int $businessId = null): bool
    {
        /*
         * Manage Side Bar is the parent visibility authority for every business
         * sidebar, including a genuine Super Admin viewing that business.
         * Super Admin functional privileges remain separate; they must not force
         * a business-disabled parent back into the rendered sidebar.
         *
         * If no business context exists, resolveConfiguredState() keeps the
         * historical permissive fallback, so central administration is not
         * accidentally hidden by the absence of a business row.
         */
        return self::resolveConfiguredState($moduleKey, $businessId)
            && self::isModuleAllowedForCurrentManagedRole($moduleKey, $businessId);
    }

    /**
     * UserManagementNew roles are an additional, user-specific child gate.
     *
     * Manage Side Bar and Manage New remain authoritative business controls.
     * This check only narrows the rendered sidebar for roles created/managed by
     * UserManagementNew. Legacy roles retain their historical behaviour, while
     * Administrators and genuine Super Admins retain full sidebar access.
     */
    public static function isModuleAllowedForCurrentManagedRole(
        ?string $moduleKey,
        ?int $businessId = null
    ): bool {
        try {
            if (!auth()->check() || self::hasSuperAdminBypass()) {
                return true;
            }

            $user = auth()->user();
            $businessId = $businessId
                ?: (int) session('business.id')
                ?: (int) session('user.business_id')
                ?: (int) ($user->business_id ?? 0);
            if ($businessId <= 0 || $user->hasRole('Admin#' . $businessId)) {
                return true;
            }

            // Read the contract from the assigned business role itself. Using
            // $user->can() here combines every role and direct permission and
            // can leak stale rights from an earlier assignment.
            $permissionSet = self::managedRolePermissionSet($businessId);
            if ($permissionSet === null) {
                return true;
            }

            $canonical = self::canonicalModuleKey($moduleKey);
            if ($canonical === '') {
                return true;
            }

            // Help Guide is a shared support utility, not a business operation
            // module. UserManagementNew does not currently publish a
            // `umn.module.help_guide.view` contract, so requiring that synthetic
            // permission hides Help Guide from every managed non-admin role.
            // Business-level visibility still follows Manage Side Bar through
            // resolveConfiguredState(); only the user-role child gate is skipped.
            if ($canonical === 'help_guide') {
                return true;
            }

            $cacheKey = (int) $user->id . '|' . $businessId . '|' . $canonical;
            if (array_key_exists($cacheKey, self::$requestManagedRoleModuleStates)) {
                return self::$requestManagedRoleModuleStates[$cacheKey];
            }

            $allowed = false;
            foreach (self::aliasesFor($canonical) as $alias) {
                if (isset($permissionSet['umn.module.' . $alias . '.view'])) {
                    $allowed = true;
                    break;
                }
            }

            return self::$requestManagedRoleModuleStates[$cacheKey] = $allowed;
        } catch (\Throwable $e) {
            // A sidebar must never make login fail during a rolling deployment.
            // Server-side role middleware remains the authoritative URL guard.
            return true;
        }
    }

    /**
     * Whether the current user is governed by a UserManagementNew role for
     * this business. Direct/stale user permissions deliberately do not opt a
     * user into the managed contract.
     */
    public static function usesManagedRoleForCurrentUser(?int $businessId = null): bool
    {
        return self::managedRolePermissionSet($businessId) !== null;
    }

    /**
     * Check one permission against the exact assigned business role.
     */
    public static function managedRoleAllowsPermission(
        string $permission,
        ?int $businessId = null
    ): bool {
        $permissionSet = self::managedRolePermissionSet($businessId);
        if ($permissionSet === null) {
            return false;
        }
        if (isset($permissionSet[$permission])) {
            return true;
        }

        // v6 compatibility: UserManagementNew permissions created before the
        // current canonical module names may still be stored under a historical
        // module alias. Treat only aliases of the SAME canonical module as
        // equivalent; no permission is borrowed from another module.
        if (preg_match('/^umn\.module\.([a-z0-9_]+)\.view$/i', $permission, $match)) {
            $canonical = self::canonicalModuleKey($match[1]);
            foreach (self::aliasesFor($canonical) as $alias) {
                if (isset($permissionSet['umn.module.' . $alias . '.view'])) {
                    return true;
                }
            }
            return false;
        }

        if (preg_match('/^umn\.page\.([a-z0-9_]+)\.view$/i', $permission, $match)) {
            $pageKey = self::normalizeKey($match[1]);
            try {
                foreach (AutomaticModuleRegistry::sidebarModules() as $moduleKey => $module) {
                    $canonical = self::canonicalModuleKey($moduleKey);
                    if ($canonical === '') {
                        continue;
                    }
                    $aliases = self::aliasesFor($canonical);
                    usort($aliases, static fn ($a, $b) => strlen($b) <=> strlen($a));
                    foreach ($aliases as $sourceAlias) {
                        if ($pageKey !== $sourceAlias && !str_starts_with($pageKey, $sourceAlias . '_')) {
                            continue;
                        }
                        $suffix = substr($pageKey, strlen($sourceAlias));
                        foreach ($aliases as $targetAlias) {
                            if (isset($permissionSet['umn.page.' . $targetAlias . $suffix . '.view'])) {
                                return true;
                            }
                        }
                        return false;
                    }
                }
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Load only UserManagementNew permissions granted by the user's assigned
     * role in the current business. If legacy data contains multiple managed
     * roles, use their intersection (least privilege) until the next user save
     * normalises the assignment to one role.
     *
     * @return array<string, bool>|null
     */
    private static function managedRolePermissionSet(?int $businessId = null): ?array
    {
        try {
            if (!auth()->check()) {
                return null;
            }

            $user = auth()->user();
            $businessId = $businessId
                ?: (int) session('business.id')
                ?: (int) session('user.business_id')
                ?: (int) ($user->business_id ?? 0);
            if ($businessId <= 0) {
                return null;
            }

            $cacheKey = (int) $user->id . '|' . $businessId;
            if (array_key_exists($cacheKey, self::$requestManagedRolePermissionSets)) {
                return self::$requestManagedRolePermissionSets[$cacheKey];
            }

            $roles = $user->roles()
                ->where('roles.business_id', $businessId)
                ->where('roles.guard_name', 'web')
                ->with('permissions')
                ->orderBy('roles.id')
                ->get();

            $managedSets = [];
            foreach ($roles as $role) {
                $names = $role->permissions
                    ->pluck('name')
                    ->filter(static fn ($name): bool => str_starts_with((string) $name, 'umn.'))
                    ->map(static fn ($name): string => (string) $name)
                    ->values()
                    ->all();
                if (!in_array('umn.managed', $names, true)) {
                    continue;
                }
                $managedSets[] = array_fill_keys($names, true);
            }

            if ($managedSets === []) {
                return self::$requestManagedRolePermissionSets[$cacheKey] = null;
            }

            $permissionSet = array_shift($managedSets);
            foreach ($managedSets as $otherSet) {
                $permissionSet = array_intersect_key($permissionSet, $otherSet);
            }

            return self::$requestManagedRolePermissionSets[$cacheKey] = $permissionSet;
        } catch (\Throwable $e) {
            // Preserve legacy access during a rolling deployment. The managed
            // middleware will apply the contract once all files are present.
            return null;
        }
    }

    /**
     * Resolve the business/package state once without any user-role bypass.
     */
    private static function resolveConfiguredState(?string $moduleKey, ?int $businessId = null): bool
    {
        if (empty($moduleKey)) {
            return true;
        }

        $businessId = $businessId ?: (int) session('user.business_id');
        if (empty($businessId) || ! self::tableExists()) {
            return true;
        }

        $canonical = self::canonicalModuleKey($moduleKey);
        if ($canonical === '') {
            return true;
        }

        $stateKey = $businessId . '|' . $canonical;
        if (array_key_exists($stateKey, self::$requestEnabledStates)) {
            return self::$requestEnabledStates[$stateKey];
        }

        // Installed standalone modules are usable only when Laravel Modules
        // explicitly marks the exact module.json name active. A saved business
        // checkbox must never override missing/false global activation because
        // the provider/routes will not exist in that state.
        if (AutomaticModuleRegistry::standaloneGlobalAvailability($canonical) === false) {
            return self::$requestEnabledStates[$stateKey] = false;
        }

        $aliases = self::aliasesFor($canonical);
        $rows = self::cachedRows($businessId);

        // Explicit Manage Side Bar disable is authoritative and always wins.
        foreach ($aliases as $key) {
            if (array_key_exists($key, $rows) && (int) $rows[$key] === 0) {
                return self::$requestEnabledStates[$stateKey] = false;
            }
        }

        /*
         | S717 authority rule.
         |
         | Parent module visibility is owned ONLY by Manage Side Bar.  The
         | retiring Super Admin > Manage page and package_details must never be
         | able to turn an explicitly enabled parent back off.  Manage New is
         | enforced separately for pages/tabs/features, and UserManagementNew
         | separately for user-specific access.
         */

        foreach ($aliases as $key) {
            if (array_key_exists($key, $rows) && (int) $rows[$key] === 1) {
                return self::$requestEnabledStates[$stateKey] = true;
            }
        }

        // Installed modules and newly introduced core sections default to on
        // until Super Admin explicitly unchecks them. Existing managed lists do
        // not silently hide a newly installed module.
        $known = AutomaticModuleRegistry::findSidebar($canonical) !== null
            || array_key_exists($canonical, self::coreSidebarDefinitions());

        return self::$requestEnabledStates[$stateKey] = ($known || $rows === []);
    }

    public static function filterEnabledModules(array $enabledModules, ?int $businessId = null): array
    {
        $businessId = $businessId ?: (int) session('user.business_id');
        $effectiveModules = $enabledModules;

        // Central Super Admin keeps its full administration catalogue. During
        // Login As Business the selected business's Manage Side Bar state is
        // authoritative, so do NOT expand the list to every installed module.
        if (self::isGenuineSuperAdmin()) {
            foreach (self::coreSidebarDefinitions() as $key => $definition) {
                $effectiveModules[] = $key;
                foreach (array_merge($definition['aliases'] ?? [], $definition['package_aliases'] ?? []) as $alias) {
                    $effectiveModules[] = $alias;
                }
            }

            try {
                foreach (AutomaticModuleRegistry::all() as $module) {
                    $effectiveModules[] = $module['key'] ?? '';
                    $effectiveModules[] = ! empty($module['key']) ? $module['key'] . '_module' : '';
                    foreach ($module['aliases'] ?? [] as $alias) {
                        $effectiveModules[] = $alias;
                    }
                }
            } catch (\Throwable $e) {
                // Retain core aliases if automatic discovery is unavailable.
            }

            return array_values(array_unique(array_filter(array_map(static function ($module) {
                return is_scalar($module) ? (string) $module : '';
            }, $effectiveModules))));
        }

        // A logged-in tenant session can contain the module list captured before
        // Super Admin changed Manage Side Bar. Merge the authoritative central
        // values so newly enabled modules appear immediately without logout.
        if (! empty($businessId) && self::tableExists()) {
            foreach (self::cachedRows($businessId) as $moduleKey => $enabled) {
                if ((int) $enabled === 1) {
                    $effectiveModules[] = $moduleKey;
                }
            }
        }

        $effectiveModules = array_values(array_unique(array_filter(array_map(static function ($module) {
            return is_scalar($module) ? (string) $module : '';
        }, $effectiveModules))));

        return array_values(array_filter($effectiveModules, function ($module) use ($businessId) {
            if (self::disabledMarkerCanonical((string) $module) !== null) {
                return false;
            }

            return self::isEnabled((string) $module, $businessId);
        }));
    }

    public static function forgetBusinessCache(int $businessId): void
    {
        $key = self::cacheKey($businessId);
        unset(
            self::$requestRows[$key],
            self::$requestCentralBusinessIdentities[$businessId],
            self::$requestPackageBlueprintScopes[$businessId],
            self::$requestSubscriptionPackageDetails[$businessId],
            self::$requestExplicitDisabledKeys[$businessId],
            self::$requestDisabledSidebarVariables[$businessId],
            self::$requestDisabledSidebarDescriptors[$businessId],
            self::$requestDisabledAutomaticPermissionDescriptors[$businessId]
        );
        self::$requestManagedRoleModuleStates = [];
        self::$requestManagedRolePermissionSets = [];
        foreach (array_keys(self::$requestEnabledStates) as $stateKey) {
            if (str_starts_with($stateKey, $businessId . '|')) {
                unset(self::$requestEnabledStates[$stateKey]);
            }
        }
        Cache::forget($key);
    }


    public static function isAnyEnabled(array $moduleKeys, ?int $businessId = null): bool
    {
        foreach ($moduleKeys as $moduleKey) {
            if (self::isEnabled($moduleKey, $businessId)) {
                return true;
            }
        }
        return false;
    }

    public static function routeModuleKeysForRequest($request): array
    {
        $path = trim((string) $request->path(), '/');
        if ($path === 'superadmin' || str_starts_with($path, 'superadmin/')) {
            // Super Admin administration pages are core routes, not optional
            // business modules. Return before module registry discovery.
            return [];
        }

        // Some historical sidebar sections are independently switchable inside
        // a larger module (for example Property > List Easy Payments). Keep that
        // exact feature gate in addition to the owning module gate.
        $moduleKeys = self::exactSidebarSectionKeysForPath($path);

        /*
         * Finance List Accounts still uses the proven core AccountController
         * URLs on many installations. Standalone Finance is the owning parent
         * when it is enabled, even though the compatibility URI remains under
         * /accounting-module/account. Resolve this BEFORE controller discovery;
         * otherwise App\Http\Controllers\AccountController would incorrectly
         * classify the request as the separately switchable core Accounting
         * Module and block a valid Finance user.
         */
        if (($path === 'accounting-module/account'
                || str_starts_with($path, 'accounting-module/account/'))
            && self::isManageSidebarEnabled('finance')) {
            $moduleKeys[] = 'finance';
            return array_values(array_unique($moduleKeys));
        }

        /*
         * Resolve the matched route BEFORE falling back to URL prefixes.
         *
         * The standalone Finance module intentionally owns several legacy
         * /accounting-module/* URLs. Prefix-first detection classified those
         * requests as the core Accounting Module and returned a false 403 when
         * Finance was enabled independently. Controller/route-name discovery is
         * authoritative because it tells us which implementation Laravel
         * actually matched:
         *   Modules\Finance\... => finance
         *   App\Http\Controllers\Account... => accounting_module (path fallback)
         */
        try {
            $route = $request->route();
            if ($route) {
                $controllerKey = AutomaticModuleRegistry::moduleKeyFromController($route->getActionName());
                if ($controllerKey) {
                    $moduleKeys[] = $controllerKey;
                    return array_values(array_unique($moduleKeys));
                }

                $coreControllerKey = self::coreModuleKeyFromControllerAction($route->getActionName());
                if ($coreControllerKey) {
                    $moduleKeys[] = $coreControllerKey;
                    return array_values(array_unique($moduleKeys));
                }

                $routeKey = AutomaticModuleRegistry::moduleKeyFromRouteName($route->getName());
                if ($routeKey) {
                    $moduleKeys[] = $routeKey;
                    return array_values(array_unique($moduleKeys));
                }
            }
        } catch (\Throwable $e) {
            // Fall back to path discovery below.
        }

        return array_values(array_unique(array_merge(
            $moduleKeys,
            self::moduleKeysForPath((string) $request->path())
        )));
    }

    /**
     * Resolve the automatically discovered page key for the matched request.
     * Tabs are enforced in the rendered UI because they do not own a route.
     *
     * @return array<int, string>
     */
    public static function permissionKeysForRequest($request): array
    {
        $moduleKeys = self::routeModuleKeysForRequest($request);
        if ($moduleKeys === []) {
            return [];
        }

        $routeName = null;
        $routePath = (string) $request->path();
        try {
            $route = $request->route();
            if ($route) {
                $routeName = $route->getName();
                if (method_exists($route, 'uri')) {
                    $routePath = (string) $route->uri();
                }
            }
        } catch (\Throwable $e) {
            // The request path remains a safe fallback for legacy routes.
        }

        $keys = [];
        foreach ($moduleKeys as $moduleKey) {
            $permissionKey = AutomaticModuleRegistry::permissionKeyForRoute(
                (string) $moduleKey,
                $routeName,
                $routePath
            );
            if ($permissionKey !== null && $permissionKey !== '') {
                $keys[] = self::normalizeKey($permissionKey);
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * Whether this permission is owned by Super Admin > Manage New.
     *
     * This lets legacy modules keep calling ModuleUtil::
     * hasThePermissionInSubscription() without falling back to the retiring
     * Manage page for a page/tab/feature that Manage New now owns. Parent
     * module keys are handled separately by Manage Side Bar.
     */
    public static function isManageNewPermissionKey(?string $permissionKey): bool
    {
        $permissionKey = self::normalizeKey((string) $permissionKey);
        if ($permissionKey === '') {
            return false;
        }

        if (self::$requestManageNewPermissionKeys === null) {
            $keys = [];
            try {
                foreach (AutomaticModuleRegistry::manageSections() as $section) {
                    foreach ((array) ($section['items'] ?? []) as $item) {
                        $key = self::normalizeKey((string) ($item['key'] ?? ''));
                        if ($key !== '') {
                            $keys[$key] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $keys = [];
            }
            self::$requestManageNewPermissionKeys = $keys;
        }

        return isset(self::$requestManageNewPermissionKeys[$permissionKey]);
    }

    /**
     * Newly discovered page/tab keys default to enabled. Only an explicit zero
     * on this business's own active subscription denies access.
     */
    public static function isAutomaticPermissionEnabled(
        ?string $permissionKey,
        ?int $businessId = null
    ): bool {
        if (self::isGenuineSuperAdmin()) {
            return true;
        }

        $permissionKey = self::normalizeKey((string) $permissionKey);
        $businessId = $businessId ?: (int) session('user.business_id');
        if ($permissionKey === '' || empty($businessId)) {
            return true;
        }

        $details = self::activeSubscriptionPackageDetails($businessId);

        if (!is_array($details) || !array_key_exists($permissionKey, $details)) {
            return true;
        }

        $value = $details[$permissionKey];
        if (is_array($value) || is_object($value)) {
            return true;
        }
        $flag = is_string($value) ? strtolower(trim($value)) : $value;

        return in_array($flag, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true);
    }

    /**
     * Exact, collision-safe gates for legacy sections nested inside a broader
     * module. Prefix comparisons are path-boundary aware, so `orders` never
     * affects `/tailoring/orders`, and Purchase/Purchases remain independent.
     *
     * @return array<int, string>
     */
    private static function exactSidebarSectionKeysForPath(string $path): array
    {
        $path = strtolower(trim($path, '/ '));
        if ($path === '') {
            return [];
        }

        $map = [
            'contacts/credit-sales' => 'list_credit_sales_page',
            'property/easy-payments' => 'list_easy_payment',
            'crm' => 'enable_crm',
            'crm-activity' => 'enable_crm',
            'crmgroups' => 'enable_crm',
            'tpos' => 'tpos_module',
            'fpos' => 'tpos_module',
            'list/fpos' => 'tpos_module',
            'backup' => 'backup_module',
            'bookings' => 'enable_booking',
            'kitchen' => 'kitchen',
            'orders' => 'orders',
            'notification-templates' => 'notification_template_module',
            'post-dated-cheques' => 'post_dated_cheque',
            'accounting-module/post-dated-cheques' => 'post_dated_cheque',
            'accounting-module/old-post-dated-cheques' => 'post_dated_cheque',
            'accounting-module/post-dated-cheques-filters' => 'post_dated_cheque',
            'accounting-module/old-post-dated-cheques-filters' => 'post_dated_cheque',
            'accounting-module/dated-cheques-party-type' => 'post_dated_cheque',
            'accounting-module/realize-cheque-deposit' => 'realize_cheque',
            'accounting-module/realize-cheque-list' => 'realize_cheque',
            'accounting-module/realized-cheques' => 'realize_cheque',
            'realize-cheque' => 'realize_cheque',
            'payday' => 'payday',
            'petro-quota' => 'petro_quota_module',
            'petro/issue-customer-bill-with-vat' => 'issue_customer_bill_vat',
            'petro/issue-customer-bill-vat' => 'issue_customer_bill_vat',
            'petro/issue-customer-bill_vat' => 'issue_customer_bill_vat',
            'petro/issue-customer-bill' => 'issue_customer_bill',
        ];

        $keys = [];
        foreach ($map as $prefix => $moduleKey) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                $keys[] = $moduleKey;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Core routes live under App\Http\Controllers, so the automatic standalone
     * module registry cannot identify them from the controller namespace. Keep a
     * small explicit controller map as a second authoritative resolver before
     * falling back to URL prefixes. This protects renamed/aliased core URLs too.
     */
    private static function coreModuleKeyFromControllerAction(?string $actionName): ?string
    {
        $controllerClass = explode('@', (string) $actionName, 2)[0] ?? '';
        if (!str_starts_with($controllerClass, 'App\\Http\\Controllers\\')) {
            return null;
        }

        $controller = substr($controllerClass, (int) strrpos($controllerClass, '\\') + 1);
        $map = [
            // Core Accounting.
            'AccountController' => 'accounting_module',
            'AccountGroupController' => 'accounting_module',
            'AccountReportsController' => 'accounting_module',
            'AccountSettingController' => 'accounting_module',
            'AccountTypeController' => 'accounting_module',
            'DefaultAccountController' => 'accounting_module',
            'DefaultAccountGroupController' => 'accounting_module',
            'DefaultAccountTypeController' => 'accounting_module',
            'DepositModuleController' => 'deposits',
            'DepositsController' => 'deposits',
            'PostdatedChequeController' => 'post_dated_cheque',
            'RealizedChequeController' => 'realize_cheque',

            // Historical/core sidebar sections that are independently
            // switchable from Manage Side Bar.
            'CRMController' => 'enable_crm',
            'CRMActivityController' => 'enable_crm',
            'CrmActivityDetailController' => 'enable_crm',
            'CrmGroupController' => 'enable_crm',
            'ContactCreditSales' => 'list_credit_sales_page',
            'TposController' => 'tpos_module',
            'BackUpController' => 'backup_module',
            'BookingController' => 'enable_booking',
            'KitchenController' => 'kitchen',
            'OrderController' => 'orders',
            'NotificationTemplateController' => 'notification_template_module',

            // Core Contacts / Customers.
            'ContactController' => 'contact_module',
            'ContactGroupController' => 'contact_module',
            'ContactSummaryController' => 'contact_module',
            'CustomerReferenceController' => 'contact_module',
            'CustomerStatementController' => 'contact_module',
            'CustomerStatementWithPaymentController' => 'contact_module',
            'CustomerPaymentController' => 'contact_module',
            'CustomerPaymentBulkController' => 'contact_module',
            'CustomerPaymentSimpleController' => 'contact_module',

            // Core Purchases.
            'PurchaseController' => 'purchases',
            'PurchaseReturnController' => 'purchases',
            'CombinedPurchaseReturnController' => 'purchases',
            'PurchasePosController' => 'purchases',
            'PurchaseSettingsController' => 'purchases',
            'ImportPurchasesController' => 'purchases',

            // Core Expenses.
            'ExpenseController' => 'expenses',
            'ExpenseCategoryController' => 'expenses',
            'ExpenseCategoryCodeController' => 'expenses',
            'ExpenseCategoryNumberController' => 'expenses',

            // Core Products / Inventory masters.
            'ProductController' => 'products',
            'ImportProductsController' => 'products',
            'ImportOpeningStockController' => 'products',
            'OpeningStockController' => 'products',
            'SellingPriceGroupController' => 'products',
            'TaxonomyController' => 'products',
            'BarcodeController' => 'products',

            // Core stock operations.
            'StockTransferController' => 'stock_transfer',
            'StockTransferRequestController' => 'stock_transfer',
            'StockAdjustmentController' => 'stock_adjustment',

            // Core Sales.
            'SellController' => 'sales',
            'SellPosController' => 'sales',
            'SellReturnController' => 'sales',
            'ReservedStocksController' => 'sales',
            'DiscountController' => 'sales',

            // Core reports.
            'ReportController' => 'reports',
            'CommonReportController' => 'reports',

            // Core Settings.
            'BusinessLocationController' => 'settings',
            'InvoiceSchemeController' => 'settings',
            'InvoiceLayoutController' => 'settings',
            'PrinterController' => 'settings',
            'TaxRateController' => 'settings',
            'GroupTaxController' => 'settings',
            'LocationSettingsController' => 'settings',

            // Core User Management.
            'UserController' => 'user_management',
            'ManageUserController' => 'user_management',
            'RoleController' => 'user_management',
            'SalesCommissionAgentController' => 'user_management',
            'UserGroupController' => 'user_management',
        ];

        return $map[$controller] ?? null;
    }

    public static function moduleKeysForUrl(?string $url): array
    {
        if (empty($url)) {
            return [];
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path)) {
            $path = $url;
        }

        return self::moduleKeysForPath((string) $path);
    }

    public static function moduleKeysForPath(?string $path): array
    {
        $path = trim((string) $path);
        if ($path === '' || $path === '/') {
            return [];
        }

        $path = trim($path, '/');

        $segments = array_values(array_filter(explode('/', $path), function ($segment) {
            return $segment !== '';
        }));

        $segment1 = self::normalizeKey($segments[0] ?? '');
        $segment2 = self::normalizeKey($segments[1] ?? '');
        $pathKey = self::normalizeKey(str_replace('/', '_', $path));

        $map = array_merge(AutomaticModuleRegistry::routePrefixMap(), self::routeModuleMap());

        foreach ([$pathKey, $segment1 . '_' . $segment2, $segment1] as $lookup) {
            $lookup = trim($lookup, '_');
            if ($lookup !== '' && isset($map[$lookup])) {
                return $map[$lookup];
            }
        }

        return [];
    }

    public static function isUrlAllowed(?string $url, ?int $businessId = null): bool
    {
        if (self::isGenuineSuperAdmin()) {
            return true;
        }

        $moduleKeys = self::moduleKeysForUrl($url);
        if (empty($moduleKeys)) {
            return true;
        }

        foreach ($moduleKeys as $moduleKey) {
            if (! self::isEnabled($moduleKey, $businessId)) {
                return false;
            }
        }

        return true;
    }

    public static function disabledModuleKeysForBusiness(?int $businessId = null): array
    {
        if (self::isGenuineSuperAdmin()) {
            return [];
        }

        $businessId = $businessId ?: (int) session('user.business_id');
        if (empty($businessId) || ! self::tableExists()) {
            return [];
        }

        $disabled = [];
        foreach (self::cachedRows($businessId) as $key => $enabled) {
            if ((int) $enabled !== 1) {
                $disabled[] = self::normalizeKey($key);
            }
        }

        return array_values(array_unique($disabled));
    }

    public static function disabledUrlPrefixesForBusiness(?int $businessId = null): array
    {
        if (self::isGenuineSuperAdmin()) {
            return [];
        }

        $businessId = $businessId ?: (int) session('user.business_id');
        if (empty($businessId) || ! self::tableExists()) {
            return [];
        }

        $disabledPrefixes = [];
        $map = array_merge(AutomaticModuleRegistry::routePrefixMap(), self::routeModuleMap());
        foreach ($map as $routePrefix => $moduleKeys) {
            foreach ((array) $moduleKeys as $moduleKey) {
                if (! self::isEnabled($moduleKey, $businessId)) {
                    $disabledPrefixes[] = str_replace('_', '-', $routePrefix);
                    $disabledPrefixes[] = str_replace('_', '/', $routePrefix);
                    $disabledPrefixes[] = $routePrefix;
                    break;
                }
            }
        }

        foreach (AutomaticModuleRegistry::all() as $module) {
            if (self::isEnabled($module['key'], $businessId)) {
                continue;
            }
            foreach ($module['route_prefixes'] ?? [] as $prefix) {
                $disabledPrefixes[] = trim((string) $prefix, '/');
            }
        }

        return array_values(array_unique(array_filter($disabledPrefixes)));
    }

    public static function routeModuleMap(): array
    {
        $map = [
            // Core ERP sections. Similar standalone modules keep their singular
            // or module-specific prefixes and are resolved by controller first.
            'accounting_module' => ['accounting_module'],
            'account' => ['accounting_module'],
            'accounts' => ['accounting_module'],
            'journal_entry' => ['accounting_module'],
            'fixed_assets' => ['accounting_module'],
            'post_dated_cheques' => ['post_dated_cheque'],
            'deposits' => ['deposits'],
            'realize_cheque' => ['realize_cheque'],

            'contacts' => ['contact_module'],
            'contact' => ['contact_module'],
            'contact_group' => ['contact_module'],
            'import_contacts' => ['contact_module'],
            'customer_reference' => ['contact_module'],
            'customer_statement' => ['contact_module'],
            'customer_payment' => ['contact_module'],
            'outstanding_received' => ['contact_module'],
            'issue_payment_detail' => ['contact_module'],

            // Core plural Purchase and standalone singular Purchase are separate.
            'purchases' => ['purchases'],
            'purchase_return' => ['purchases'],
            'purchase_pos' => ['purchases'],
            'import_purchases' => ['purchases'],
            'purchase' => ['purchase'],

            // Core Expenses and Expenses-New are separate.
            'expenses' => ['expenses'],
            'expense_categories' => ['expenses'],
            'expense_categories_code' => ['expenses'],
            'expense_category_codes' => ['expenses'],
            'expense_categories_number' => ['expenses'],
            'expense' => ['expenses'],
            'save_add_expense_data' => ['expenses'],
            'get_expense_account_category_id' => ['expenses'],

            'products' => ['products'],
            'import_products' => ['products'],
            'variation_templates' => ['products'],
            'selling_price_group' => ['products'],
            'warranties' => ['products'],
            'units' => ['products'],
            'brands' => ['products'],
            'categories' => ['products'],

            'stock_transfers' => ['stock_transfer'],
            'stock_transfers_request' => ['stock_transfer'],
            'stock_adjustments' => ['stock_adjustment'],
            'stock_settings' => ['stock_adjustment'],

            'sells' => ['sales'],
            'sales' => ['sales'],
            'pos' => ['sales'],
            'sell_return' => ['sales'],
            'quotations' => ['sales'],
            'drafts' => ['sales'],
            'shipments' => ['sales'],
            'discount' => ['sales'],
            'import_sales' => ['sales'],
            'reserved_stocks' => ['sales'],

            'reports' => ['reports'],
            'business_settings' => ['settings'],
            'business_update' => ['settings'],
            'business_day_end' => ['settings'],
            'business_location' => ['settings'],
            'invoice_schemes' => ['settings'],
            'invoice_layouts' => ['settings'],
            'printers' => ['settings'],
            'tax_rates' => ['settings'],
            'barcode_settings' => ['settings'],

            'users' => ['user_management'],
            'user' => ['user_management'],
            'roles' => ['user_management'],
            'sales_commission_agents' => ['user_management'],

            'pumper_dashboard' => ['pumper_dashboard'],
            'pump_operator' => ['pumper_dashboard'],
            'pumper' => ['pumper_dashboard'],

            // Standalone/common modules.
            'sw' => ['sw'],
            'sw_module' => ['sw'],
            'finance' => ['finance'],
            'finance_module' => ['finance'],
            'banking' => ['banking_module'],
            'banking_module' => ['banking_module'],
            'finance_reports' => ['finance_reports'],
            'customers' => ['customers'],
            'customer' => ['customers'],
            'suppliers' => ['suppliers'],
            'supplier' => ['suppliers'],
            'sms' => ['sms_module'],
            'sms_module' => ['sms_module'],
            'petro' => ['petro'],
            'petro_pd' => ['petro_pd'],
            'petropd' => ['petro_pd'],
            'mpcs' => ['mpcs'],
            'myhealth' => ['myhealth_module'],
            'my_health_members' => ['myhealthmembers_module'],
            'membership' => ['membership_module'],
            'membership_new' => ['membership_new_module'],
            'leads' => ['leads_module'],
            'leads_new' => ['leads_new_module'],
            'distribution' => ['distribution_module'],
            'chequer' => ['chequer_module'],
            'chequer_module' => ['chequer_module'],
            'cheque' => ['chequer_module'],
            'auto_service' => ['auto_service'],
            'auto_repair_services' => ['auto_repair_services'],
            'hr_manager' => ['hr_manager'],
            'hr' => ['hr'],
            'communication_hub' => ['communication_hub'],
            'assets' => ['asset_module'],
            'asset' => ['asset_module'],
            'fleet' => ['fleet_module'],
            'hms' => ['hms_module'],
            'crm' => ['crm_module'],
            'manufacturing' => ['manufacturing_module'],
            'repair' => ['repair_module'],
            'installments' => ['installment_module'],
            'spreadsheet' => ['spreadsheet'],
            'vat' => ['vat_module'],
            'day_end' => ['day_end_module'],
            'tasks_management' => ['tasks_management'],
            'customized_reports' => ['customized_reports_module'],
            'notification' => ['notification_module'],
            'notifications' => ['notification_module'],
        ];

        // Add every declared route prefix from the shared core definitions.
        foreach (self::coreSidebarDefinitions() as $canonical => $definition) {
            foreach ($definition['route_prefixes'] ?? [] as $prefix) {
                $key = self::normalizeKey((string) $prefix);
                if ($key !== '' && !isset($map[$key])) {
                    $map[$key] = [$canonical];
                }
            }
        }

        return $map;
    }

    private static function cachedRows(int $businessId): array
    {
        $cacheKey = self::cacheKey($businessId);

        if (array_key_exists($cacheKey, self::$requestRows)) {
            return self::$requestRows[$cacheKey];
        }

        /*
         * Do not persist Manage Side Bar state across HTTP requests.
         *
         * Super Admin saves this value from the central domain while business
         * users read it from a tenant domain. Numeric business ids collide
         * between databases, so v8 resolves the CENTRAL business by:
         *
         *   1. global_uid exact match;
         *   2. tenant_id + company_number exact match;
         *   3. globally unique company_number compatibility match;
         *   4. tenant-local row only when CENTRAL cannot be resolved safely.
         *
         * A tenant business id is never used directly against CENTRAL.
         */
        return self::$requestRows[$cacheKey] = (function () use ($businessId) {
            try {
                $stored = null;
                $centralIdentity = self::resolvedCentralBusinessIdentity($businessId);

                if (is_array($centralIdentity)
                    && array_key_exists('enabled_modules', $centralIdentity)) {
                    $stored = $centralIdentity['enabled_modules'];
                }

                // Compatibility fallback for an older/unreconciled tenant.
                // It is used only when no safe CENTRAL identity could be found.
                if ($stored === null) {
                    try {
                        $stored = Business::query()
                            ->whereKey($businessId)
                            ->value('enabled_modules');
                    } catch (\Throwable $tenantException) {
                        $stored = null;
                    }
                }

                if ($stored === null) {
                    $sessionBusiness = session('business');
                    if ((int) data_get($sessionBusiness, 'id') === $businessId) {
                        $stored = data_get($sessionBusiness, 'enabled_modules');
                    }
                }

                if (is_string($stored)) {
                    $decoded = json_decode($stored, true);
                    $stored = is_array($decoded) ? $decoded : [];
                }
                $stored = is_array($stored) ? $stored : [];

                $enabled = [];
                $disabled = [];
                $walk = function ($items) use (&$walk, &$enabled, &$disabled): void {
                    foreach ((array) $items as $key => $value) {
                        if (!is_int($key) && !is_array($value) && !is_object($value)) {
                            $flag = is_string($value) ? strtolower(trim($value)) : $value;
                            $canonical = self::canonicalModuleKey((string) $key);
                            if ($canonical !== '') {
                                if (in_array($flag, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true)) {
                                    $enabled[$canonical] = true;
                                } elseif (in_array($flag, [0, '0', false, 'false', 'no', 'off', 'disabled', ''], true)) {
                                    $disabled[$canonical] = true;
                                }
                            }
                            continue;
                        }

                        if (is_array($value)) {
                            $walk($value);
                            continue;
                        }
                        if (!is_scalar($value)) {
                            continue;
                        }

                        $text = trim((string) $value);
                        if ($text === '') {
                            continue;
                        }

                        // Manage Side Bar stores unchecked modules as explicit
                        // disabled markers inside the enabled_modules array so an
                        // absent key can still mean "new module / no decision yet".
                        // SIDEBAR-IDENTITY-V8 accidentally dropped this marker
                        // decode while refactoring central-business resolution,
                        // which made every unchecked installed module fall through
                        // to the default-visible branch on the next request.
                        $marker = self::disabledMarkerCanonical($text);
                        if ($marker !== null) {
                            $disabled[$marker] = true;
                            continue;
                        }

                        $canonical = self::canonicalModuleKey($text);
                        if ($canonical !== '') {
                            $enabled[$canonical] = true;
                        }
                    }
                };
                $walk($stored);

                $states = [];
                foreach ($enabled as $key => $_) {
                    $states[$key] = 1;
                }
                foreach ($disabled as $key => $_) {
                    $states[$key] = 0;
                }

                return $states;
            } catch (\Throwable $e) {
                return [];
            }
        })();
    }

    /**
     * Resolve the current database's business to the CENTRAL business registry
     * without assuming numeric ids are globally meaningful.
     *
     * @return array<string, mixed>|null
     */
    private static function resolvedCentralBusinessIdentity(int $businessId): ?array
    {
        if (array_key_exists($businessId, self::$requestCentralBusinessIdentities)) {
            return self::$requestCentralBusinessIdentities[$businessId];
        }

        try {
            if ($businessId <= 0 || !Schema::hasTable('business')) {
                return self::$requestCentralBusinessIdentities[$businessId] = null;
            }

            $localColumns = ['id'];
            foreach (['global_uid', 'tenant_id', 'company_number', 'name'] as $column) {
                try {
                    if (Schema::hasColumn('business', $column)) {
                        $localColumns[] = $column;
                    }
                } catch (\Throwable $e) {
                    // Rolling deployment: use the identity columns that exist.
                }
            }

            $local = DB::table('business')
                ->where('id', $businessId)
                ->first(array_values(array_unique($localColumns)));

            if (!$local) {
                return self::$requestCentralBusinessIdentities[$businessId] = null;
            }

            $connection = self::centralConnectionName();
            if (!Schema::connection($connection)->hasTable('business')) {
                return self::$requestCentralBusinessIdentities[$businessId] = null;
            }

            $hasCentralUid = Schema::connection($connection)->hasColumn('business', 'global_uid');
            $hasCentralTenant = Schema::connection($connection)->hasColumn('business', 'tenant_id');
            $hasCentralCompany = Schema::connection($connection)->hasColumn('business', 'company_number');
            $hasCentralEnabled = Schema::connection($connection)->hasColumn('business', 'enabled_modules');

            $select = ['id'];
            foreach (['global_uid', 'tenant_id', 'company_number', 'name', 'enabled_modules'] as $column) {
                try {
                    if (Schema::connection($connection)->hasColumn('business', $column)) {
                        $select[] = $column;
                    }
                } catch (\Throwable $e) {
                    // Keep the lookup compatible with older central schemas.
                }
            }
            $select = array_values(array_unique($select));

            $central = null;
            $localUid = trim((string) ($local->global_uid ?? ''));
            $companyNumber = trim((string) ($local->company_number ?? ''));

            // 1) Canonical estate-wide identity.
            if ($localUid !== '' && $hasCentralUid) {
                $central = DB::connection($connection)
                    ->table('business')
                    ->where('global_uid', $localUid)
                    ->first($select);
            }

            // 2) Compatibility identity scoped to the current tenant.
            if (!$central && $companyNumber !== '' && $hasCentralCompany) {
                $tenantKeys = self::currentTenantIdentityKeys();
                if ($hasCentralTenant && $tenantKeys !== []) {
                    $matches = DB::connection($connection)
                        ->table('business')
                        ->where('company_number', $companyNumber)
                        ->whereIn('tenant_id', $tenantKeys)
                        ->limit(2)
                        ->get($select);

                    if ($matches->count() === 1) {
                        $central = $matches->first();
                    }
                }
            }

            // 3) Very old installations may have company_number but no tenant_id.
            // Accept it only when it identifies exactly one CENTRAL business.
            if (!$central && $companyNumber !== '' && $hasCentralCompany) {
                $matches = DB::connection($connection)
                    ->table('business')
                    ->where('company_number', $companyNumber)
                    ->limit(2)
                    ->get($select);

                if ($matches->count() === 1) {
                    $central = $matches->first();
                }
            }

            if (!$central) {
                return self::$requestCentralBusinessIdentities[$businessId] = null;
            }

            return self::$requestCentralBusinessIdentities[$businessId] = [
                'id' => (int) ($central->id ?? 0),
                'global_uid' => trim((string) ($central->global_uid ?? $localUid)),
                'tenant_id' => (string) ($central->tenant_id ?? ''),
                'company_number' => (string) ($central->company_number ?? $companyNumber),
                'name' => (string) ($central->name ?? ''),
                'enabled_modules' => $hasCentralEnabled
                    ? ($central->enabled_modules ?? null)
                    : null,
            ];
        } catch (\Throwable $e) {
            return self::$requestCentralBusinessIdentities[$businessId] = null;
        }
    }

    /**
     * Stable strings that can identify the current tenant in CENTRAL.
     *
     * Older rows can store tenant_id as "2003", "nivasa_2003", or the tenancy
     * database name. Keep all safe equivalents; never use them as business ids.
     *
     * @return array<int, string>
     */
    private static function currentTenantIdentityKeys(): array
    {
        $keys = [];

        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                $tenant = function_exists('current_tenant') ? current_tenant() : tenant();
                $data = (array) ($tenant->data ?? []);

                foreach ([
                    (string) ($tenant->id ?? ''),
                    (string) data_get($data, 'tenancy_db_name', ''),
                    (string) data_get($data, 'database', ''),
                    (string) data_get($data, 'db_name', ''),
                ] as $value) {
                    if (trim($value) !== '') {
                        $keys[] = trim($value);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Current DB name below remains a safe tenant hint.
        }

        try {
            $database = (string) DB::connection()->getDatabaseName();
            if (trim($database) !== '') {
                $keys[] = trim($database);
            }
        } catch (\Throwable $e) {
            // No additional key available.
        }

        $expanded = [];
        foreach ($keys as $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }

            $expanded[$key] = true;

            if (preg_match('/(?:^|_)(\d+)$/', $key, $m)) {
                $expanded[$m[1]] = true;
            }
        }

        return array_keys($expanded);
    }

    private static function packageBlueprintAllows(array $aliases, int $businessId): ?bool
    {
        $scope = self::packageBlueprintScope($businessId);
        if ($scope === null) {
            return null;
        }

        foreach ($aliases as $alias) {
            $alias = self::normalizeKey((string) $alias);
            if ($alias !== '' && ! empty($scope[$alias])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reads the active system subscription once per request. This keeps normal
     * businesses at zero extra queries and package-controlled businesses at one
     * small query regardless of how many sidebar links are rendered.
     */
    private static function packageBlueprintScope(int $businessId): ?array
    {
        if (array_key_exists($businessId, self::$requestPackageBlueprintScopes)) {
            return self::$requestPackageBlueprintScopes[$businessId];
        }

        $details = self::activeSubscriptionPackageDetails($businessId);
        if (!is_array($details) || empty($details['_package_permission_blueprint'])) {
            return self::$requestPackageBlueprintScopes[$businessId] = null;
        }

        $scope = [];
        foreach ($details as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $key = self::normalizeKey((string) $key);
            if ($key === '' || str_starts_with($key, '_')) {
                continue;
            }

            $flag = is_string($value) ? strtolower(trim($value)) : $value;
            $scope[$key] = in_array($flag, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true) ? 1 : 0;
        }

        return self::$requestPackageBlueprintScopes[$businessId] = $scope;
    }

    /**
     * Read package_details from the current business subscription once. The
     * central business_id filter is the isolation boundary between businesses.
     */
    private static function activeSubscriptionPackageDetails(int $businessId): ?array
    {
        if (array_key_exists($businessId, self::$requestSubscriptionPackageDetails)) {
            return self::$requestSubscriptionPackageDetails[$businessId];
        }

        try {
            $today = date('Y-m-d');

            /*
             |--------------------------------------------------------------
             | MA-007: find the subscription by global_uid, not business_id.
             |--------------------------------------------------------------
             | This is the method MA 007 section 2 identified as the heart of
             | the problem: permissions live in subscriptions.package_details,
             | keyed by business_id, and are read from CENTRAL.
             |
             | Every tenant has its own business 1, 2, 3. Central has one set
             | of subscription rows per id. So a tenant's business 2 read a
             | different company's package flags.
             |
             | Proved live on 26 Aug: tenant nivasa_2003 business 2 is "MPCS
             | Filling Station". Every central subscription for business_id 2
             | belongs to "Cool50", with purchases = 0. The sidebar gate is
             |
             |     package flag AND sidebar visibility
             |
             | so MPCS could never show Purchases however it was configured -
             | Cool50's flag answered for it.
             |
             | global_uid is unique across the estate. Where a business has no
             | uid, central is skipped rather than matched by id: no answer is
             | better than another company's answer.
             |
             | subscriptions.global_uid was added and backfilled by
             | business:backfill-registry.
             */
            $connection = self::centralConnectionName();
            $centralIdentity = self::resolvedCentralBusinessIdentity($businessId);
            $globalUid = trim((string) ($centralIdentity['global_uid'] ?? ''));
            $centralBusinessId = (int) ($centralIdentity['id'] ?? 0);

            $hasUidColumn = false;
            try {
                $hasUidColumn = Schema::connection($connection)
                    ->hasColumn('subscriptions', 'global_uid');
            } catch (\Throwable $schemaException) {
                $hasUidColumn = false;
            }

            /*
             | S717 / Manage New authority.
             |
             | Manage New writes the CENTRAL active subscription. v8 resolves
             | that row through the same CENTRAL business identity as Manage
             | Side Bar. This safely supports installations where tenant-local
             | business 2 is CENTRAL business 19, without ever joining by the
             | colliding numeric id.
             */
            $centralQuery = null;
            try {
                if ($globalUid !== '' && $hasUidColumn) {
                    $centralQuery = DB::connection($connection)
                        ->table('subscriptions')
                        ->where('global_uid', $globalUid);
                } elseif ($centralBusinessId > 0
                    && Schema::connection($connection)->hasColumn('subscriptions', 'business_id')) {
                    $centralQuery = DB::connection($connection)
                        ->table('subscriptions')
                        ->where('business_id', $centralBusinessId);
                }
            } catch (\Throwable $centralIdentityException) {
                $centralQuery = null;
            }

            if ($centralQuery) {
                $row = $centralQuery
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $today)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('end_date', '>=', $today)
                            ->orWhereNull('end_date');
                    })
                    ->whereNull('deleted_at')
                    ->orderByDesc('end_date')
                    ->orderByDesc('start_date')
                    ->orderByDesc('id')
                    ->first(['package_details']);

                if ($row) {
                    $details = json_decode((string) ($row->package_details ?? ''), true);

                    return self::$requestSubscriptionPackageDetails[$businessId] = is_array($details)
                        ? $details
                        : null;
                }
            }

            // Legacy/rolling-deployment fallback only. A tenant copy is used
            // when no safe central global_uid match can be resolved.
            $tenantRow = null;
            try {
                $tenantRow = DB::table('subscriptions')
                    ->where('business_id', $businessId)
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $today)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('end_date', '>=', $today)
                            ->orWhereNull('end_date');
                    })
                    ->whereNull('deleted_at')
                    ->orderByDesc('end_date')
                    ->orderByDesc('start_date')
                    ->orderByDesc('id')
                    ->first(['package_details']);
            } catch (\Throwable $tenantSubException) {
                $tenantRow = null;
            }

            if ($tenantRow) {
                $tenantDetails = json_decode((string) ($tenantRow->package_details ?? ''), true);

                return self::$requestSubscriptionPackageDetails[$businessId] = is_array($tenantDetails)
                    ? $tenantDetails
                    : null;
            }

            return self::$requestSubscriptionPackageDetails[$businessId] = null;

        } catch (\Throwable $e) {
            // Never break the base application on a rolling/legacy schema.
            return self::$requestSubscriptionPackageDetails[$businessId] = null;
        }
    }

    private static function centralConnectionName(): string
    {
        if (! empty(config('database.connections.system.database'))) {
            return 'system';
        }

        return (string) config('tenancy.database.central_connection', config('database.default', 'mysql'));
    }

    private static function cacheKey(int $businessId): string
    {
        $connection = self::centralConnectionName();
        $database = (string) config('database.connections.' . $connection . '.database', '');

        // Do not include the request host. Super Admin saves on the central host,
        // while users read the sidebar on tenant hosts. One shared key lets the
        // save operation invalidate every tenant-domain view immediately.
        return 'gpo:sidebar:v7-v6patch:' . sha1($connection . '|' . $database) . ':' . $businessId;
    }

    public static function normalizeKey(string $key): string
    {
        return AutomaticModuleRegistry::normalizeKey($key);
    }

    public static function canonicalModuleKey(?string $key): string
    {
        $normal = self::normalizeKey((string) $key);
        if ($normal === '') {
            return '';
        }

        foreach (self::coreSidebarDefinitions() as $canonical => $definition) {
            $checks = array_merge([$canonical, $canonical . '_module', $definition['title'] ?? ''], $definition['aliases'] ?? []);
            foreach ($checks as $check) {
                if ($normal === self::normalizeKey((string) $check)) {
                    return $canonical;
                }
            }
        }

        $automatic = AutomaticModuleRegistry::findSidebar($normal);
        if ($automatic) {
            return (string) $automatic['key'];
        }

        return AutomaticModuleRegistry::canonicalKey($normal);
    }

    private static function disabledMarkerCanonical(string $value): ?string
    {
        $prefix = AutomaticModuleRegistry::DISABLED_MARKER_PREFIX;
        if (!str_starts_with($value, $prefix)) {
            return null;
        }

        $canonical = self::canonicalModuleKey(substr($value, strlen($prefix)));

        return $canonical !== '' ? $canonical : null;
    }

    private static function aliasesFor(string $key): array
    {
        $key = self::canonicalModuleKey($key);
        if (isset(self::$requestAliases[$key])) {
            return self::$requestAliases[$key];
        }

        $aliases = [$key, $key . '_module'];
        $definition = self::coreSidebarDefinitions()[$key] ?? null;
        if ($definition) {
            $aliases = array_merge($aliases, $definition['aliases'] ?? []);
        }

        $map = [
            'stock_transfer' => ['stock_transfers'],
            'stock_adjustment' => ['stock_adjustments'],
            'accounting_module' => ['access_account', 'account', 'accounts', 'accounting'],
            'type_of_service' => ['types_of_service'],
            'enable_subscription' => ['subscription'],
            'enable_booking' => ['booking'],
            'sms_module' => ['smsmodule_module', 'enable_sms'],
            'myhealth_module' => ['my_health_module', 'myhealthmembers_module', 'myhealth'],
            'mpcs' => ['mpcs_module'],
            'hr_manager' => ['hrmanager_module'],
            'communication_hub' => ['communicationhub_module', 'communication_hub_module', 'communicationhub'],
            'leads_new' => ['leads_new_module', 'leadsnew_module', 'enable_leads_new'],
            'customers' => ['customers_module', 'customer_module'],
            // Standalone Chequer must remain independent from the legacy
            // enable_cheque_writing parent switch.
            'chequer' => ['chequer_module', 'cheque_write_module', 'cheque_writing_module'],
            'distribution' => ['distribution_module'],
            'products_new' => ['products_new_module', 'productsnew_module', 'product_new_module', 'productnew_module'],
            // Auto Service and Auto Repair Services are separate standalone
            // modules; do not share the old combined alias.
            'auto_service' => ['autoservice_module', 'auto_service_module'],
            'sw' => ['sw_module'],
            'finance' => ['finance_module'],
            'banking' => ['banking_module'],
            'finance_reports' => ['finance_reports_module', 'finance_report_module', 'financereports_module', 'financereports'],
            // Expenses-New stays independent from core Expenses.
            'expenses_new' => ['expenses_new_module', 'expense_manager', 'expense_manager_module', 'expensemanager_module'],
            'petro' => ['petro_module', 'enable_petro_module'],
            'petro_pd' => ['petro_pd_module', 'petro_p_d_module', 'petropd'],
            'petro_general' => ['petro_general_module'],
            'petro_direct' => ['petro_direct_module'],
            'daily_collection_sw' => ['daily_collection_sw_module', 'dailycollectionsw', 'dailycollectionsw_module'],
            'daily_collection' => ['daily_collection_module', 'dailycollection'],
            'airline_ticketing_new' => [
                'airline_new',
                'airline_new_module',
                'airline_ticketing',
                'airline_ticketing_module',
                'airline_ticketing_new_module',
                'airlineticketing',
                'airlineticketing_module',
                'airlineticketingnew',
                'airlineticketingnew_module',
            ],
            'airline_ticketing' => [
                'airline_new',
                'airline_new_module',
                'airline_ticketing_new',
                'airline_ticketing_new_module',
                'airlineticketing',
                'airlineticketing_module',
                'airlineticketingnew',
                'airlineticketingnew_module',
            ],
            'tea_estate_management' => [
                'tea_estate',
                'tea_estate_module',
                'tea_estate_management_module',
                'teaestatemanagement',
                'teaestatemanagement_module',
            ],
            'restaurant_new' => [
                'restaurant_new_module',
                'restaurantnew',
                'restaurantnew_module',
            ],
        ];

        if (isset($map[$key])) {
            $aliases = array_merge($aliases, $map[$key]);
        }

        $module = AutomaticModuleRegistry::findSidebar($key);
        if ($module) {
            $aliases = array_merge($aliases, $module['aliases'] ?? [], [$module['key'], $module['key'] . '_module']);
        }

        $aliases = array_values(array_unique(array_filter(array_map([self::class, 'normalizeKey'], $aliases))));

        // Never borrow another module's exact canonical key as an alias. This
        // keeps similarly named installed modules independent (for example POS
        // versus POS Module) even when an old naming convention generated the
        // same `<key>_module` value for both.
        try {
            $canonicalKeys = array_fill_keys(array_keys(AutomaticModuleRegistry::sidebarModules()), true);
            $aliases = array_values(array_filter($aliases, static function ($alias) use ($canonicalKeys, $key): bool {
                return $alias === $key || ! isset($canonicalKeys[$alias]);
            }));
        } catch (\Throwable $e) {
            // Keep the normalised aliases if module discovery is unavailable.
        }

        return self::$requestAliases[$key] = $aliases;
    }

    /**
     * Canonical keys that were explicitly unchecked in Manage Side Bar.
     *
     * cachedRows() expands old aliases, so this also recognises historical keys
     * such as enable_crm, list_credit_sales_page and ezyinvoice_module without
     * requiring changes in those modules.
     *
     * @return array<int, string>
     */
    public static function explicitDisabledCanonicalKeysForBusiness(?int $businessId = null): array
    {
        $businessId = $businessId ?: (int) session('user.business_id');
        if (empty($businessId) || ! self::tableExists()) {
            return [];
        }
        if (array_key_exists($businessId, self::$requestExplicitDisabledKeys)) {
            return self::$requestExplicitDisabledKeys[$businessId];
        }

        $disabled = [];
        foreach (self::cachedRows($businessId) as $key => $enabled) {
            if ((int) $enabled !== 0) {
                continue;
            }

            $canonical = self::canonicalModuleKey((string) $key);
            if ($canonical !== '') {
                $disabled[$canonical] = true;
            }
        }

        $keys = array_keys($disabled);
        sort($keys, SORT_NATURAL | SORT_FLAG_CASE);

        return self::$requestExplicitDisabledKeys[$businessId] = $keys;
    }

    /**
     * Blade variable names that must be forced off for the current business.
     *
     * This is a compatibility bridge for the large legacy sidebar: modules keep
     * their existing variables and partials, while one central policy applies the
     * saved Manage Side Bar state. No module controller, route or page is changed.
     *
     * @return array<int, string>
     */
    public static function disabledSidebarVariableKeysForBusiness(?int $businessId = null): array
    {
        $businessId = $businessId ?: (int) session('user.business_id');
        if (empty($businessId) || ! self::tableExists()) {
            return [];
        }
        if (array_key_exists($businessId, self::$requestDisabledSidebarVariables)) {
            return self::$requestDisabledSidebarVariables[$businessId];
        }

        $disabledKeys = [];
        foreach (self::disabledSidebarDescriptorsForBusiness($businessId) as $descriptor) {
            $canonical = self::canonicalModuleKey((string) ($descriptor['key'] ?? ''));
            if ($canonical !== '') {
                $disabledKeys[$canonical] = true;
            }
        }
        foreach (self::explicitDisabledCanonicalKeysForBusiness($businessId) as $canonical) {
            $disabledKeys[$canonical] = true;
        }

        // Variables derived after package/module hydration need to follow the same
        // parent switch. These are sidebar-only flags; functional access remains
        // governed by isEnabled() and the middleware.
        $derivedVariables = [
            'products' => ['products'],
            'purchases' => ['purchase'],
            'stock_transfer' => ['stock_transfer'],
            'stock_adjustment' => ['stock_adjustment'],
            'expenses' => ['expenses'],
            'sales' => ['sale_module'],
            'reports' => ['report_module'],
            'settings' => ['settings_module'],
            'user_management' => ['user_management_module'],
            'pumper_dashboard' => ['pump_operator_dashboard', 'can_view_pumper_dashboard_sidebar'],
            'contact_module' => ['contact_module'],
            'accounting_module' => ['access_account', 'accounting_module'],
            'sw' => ['sw_module'],
            'finance' => ['finance_module'],
            'daily_collection' => [
                'daily_collection',
                'daily_collection_enabled',
                'show_daily_collection_menu',
                'can_view_daily_collection_menu',
            ],
            'daily_collection_sw' => [
                'daily_collection_sw',
                'daily_collection_sw_enabled',
                'can_view_daily_collection_sw_menu',
            ],
            'petro_pd' => ['petro_pd_module', 'show_petro_pd_menu'],
            'membership' => ['membership_module', 'show_membership_menu'],
            'membership_new' => ['membership_new_module', 'show_membership_new_menu'],
            'sales_agent' => ['sales_agent_module', 'can_view_sales_agent_sidebar'],
            'agent' => ['agent_module', 'can_view_agent_sidebar'],
            'stock_reports' => ['stock_report', 'can_view_stock_reports_sidebar'],
        ];

        /*
         * A small number of old package variables share a word with a newer
         * standalone module. Do not zero the old section while disabling the
         * newer module. The standalone sidebar remains hidden by its dedicated
         * <key>_module flag/isVisibleInSidebar check and the DOM guard.
         */
        $protectedVariables = [
            // `$purchase` is the original Purchases (Core) package flag, while
            // `$purchase_module` belongs to Modules/Purchase.
            'purchase' => ['purchase'],
        ];

        $variables = [];
        foreach (array_keys($disabledKeys) as $canonical) {
            $protected = array_fill_keys($protectedVariables[$canonical] ?? [], true);
            foreach (self::aliasesFor($canonical) as $alias) {
                if (! isset($protected[$alias])) {
                    $variables[$alias] = true;
                }
            }
            foreach ($derivedVariables[$canonical] ?? [] as $variable) {
                $variables[self::normalizeKey((string) $variable)] = true;
            }
        }

        $keys = array_values(array_filter(array_keys($variables), static function ($key): bool {
            return preg_match('/^[a-z_][a-z0-9_]*$/', (string) $key) === 1;
        }));
        sort($keys, SORT_NATURAL | SORT_FLAG_CASE);

        return self::$requestDisabledSidebarVariables[$businessId] = $keys;
    }

    /**
     * Label-only aliases used by the DOM safety net. They are never used to
     * change another module's PHP variable or functional access state.
     *
     * @return array<int, string>
     */
    private static function sidebarMatchVariants(string $key, array $aliases = [], string $title = ''): array
    {
        $variants = [];
        $queue = array_merge([$key, $title], $aliases);

        while ($queue !== []) {
            $value = self::normalizeKey((string) array_shift($queue));
            if ($value === '' || isset($variants[$value])) {
                continue;
            }
            $variants[$value] = true;

            $stripped = $value;
            if (str_starts_with($stripped, 'enable_')) {
                $queue[] = substr($stripped, strlen('enable_'));
            }
            foreach (['_module', '_page', '_main', '_enabled', '_sub_menu'] as $suffix) {
                if (str_ends_with($stripped, $suffix) && strlen($stripped) > strlen($suffix)) {
                    $queue[] = substr($stripped, 0, -strlen($suffix));
                }
            }
            if (str_starts_with($stripped, 'list_') && str_ends_with($stripped, '_page')) {
                $queue[] = substr($stripped, strlen('list_'), -strlen('_page'));
            }
        }

        return array_keys($variants);
    }

    /**
     * Conservative URL guesses for old static sidebar switches that have no
     * module.json metadata. A stripped prefix is skipped when it belongs to a
     * different standalone module, preventing similar names from colliding.
     *
     * @return array<int, string>
     */
    private static function guessedRoutePrefixesFor(string $canonical, array $aliases): array
    {
        $prefixes = [];
        foreach (self::sidebarMatchVariants($canonical, $aliases) as $variant) {
            $owner = AutomaticModuleRegistry::findSidebar($variant);
            if ($owner && (string) ($owner['key'] ?? '') !== $canonical) {
                continue;
            }

            $path = trim(str_replace('_', '-', $variant), '-/ ');
            if ($path !== '') {
                $prefixes[$path] = true;
            }
        }

        return array_keys($prefixes);
    }

    /**
     * Metadata consumed by the sidebar visibility helper. The server middleware
     * remains authoritative for direct URL requests; this list only removes the
     * disabled parent and child menu entries from the rendered sidebar.
     */
    public static function disabledSidebarDescriptorsForBusiness(?int $businessId = null): array
    {
        $businessId = $businessId ?: (int) session('user.business_id');
        if (empty($businessId) || !self::tableExists()) {
            return [];
        }
        if (array_key_exists($businessId, self::$requestDisabledSidebarDescriptors)) {
            return self::$requestDisabledSidebarDescriptors[$businessId];
        }

        $descriptors = [];
        $catalogue = [];
        foreach (self::coreSidebarDefinitions() as $key => $definition) {
            $catalogue[$key] = [
                'key' => $key,
                'title' => $definition['title'] ?? $key,
                'aliases' => $definition['aliases'] ?? [],
                'route_prefixes' => $definition['route_prefixes'] ?? [],
            ];
        }
        foreach (AutomaticModuleRegistry::sidebarModules() as $key => $module) {
            $catalogue[$key] = [
                'key' => $key,
                'title' => $module['title'] ?? $key,
                'aliases' => $module['aliases'] ?? [],
                'route_prefixes' => $module['route_prefixes'] ?? [],
            ];
        }

        /*
         * Manage Side Bar also contains historical/static switches that do not
         * have a module folder (enable_crm, list_credit_sales_page, ezy_products,
         * and similar package-era keys). Include every explicit disabled marker
         * so the main sidebar policy covers those sections too.
         */
        foreach (self::explicitDisabledCanonicalKeysForBusiness($businessId) as $key) {
            if (isset($catalogue[$key])) {
                continue;
            }

            $aliases = self::aliasesFor($key);
            $catalogue[$key] = [
                'key' => $key,
                'title' => ucwords(str_replace('_', ' ', $key)),
                'aliases' => $aliases,
                'route_prefixes' => self::guessedRoutePrefixesFor($key, $aliases),
            ];
        }

        /*
         * Build collision maps across the complete catalogue. The browser guard
         * must never hide another module merely because two old modules share a
         * short label or URL prefix (CRM/CRM Module, POS/Sales, Purchase/Purchases).
         */
        $labelOwners = [];
        $prefixOwners = [];
        foreach ($catalogue as $catalogueKey => $catalogueModule) {
            $catalogueLabels = self::sidebarMatchVariants(
                $catalogueKey,
                array_values(array_unique(array_merge(
                    self::aliasesFor($catalogueKey),
                    $catalogueModule['aliases'] ?? []
                ))),
                (string) ($catalogueModule['title'] ?? '')
            );
            foreach ($catalogueLabels as $label) {
                $labelOwners[$label][$catalogueKey] = true;
            }

            foreach (($catalogueModule['route_prefixes'] ?? []) as $prefix) {
                $normal = self::normalizeKey((string) $prefix);
                if ($normal !== '') {
                    $prefixOwners[$normal][$catalogueKey] = true;
                }
            }
        }
        // Include core/legacy path ownership that is resolved outside the
        // automatic registry. This catches POS/Sales and similar shared paths.
        foreach (self::routeModuleMap() as $prefix => $owners) {
            $normal = self::normalizeKey((string) $prefix);
            foreach ((array) $owners as $owner) {
                $canonicalOwner = self::canonicalModuleKey((string) $owner);
                if ($normal !== '' && $canonicalOwner !== '') {
                    $prefixOwners[$normal][$canonicalOwner] = true;
                }
            }
        }

        foreach ($catalogue as $key => $module) {
            if (self::isVisibleInSidebar($key, $businessId)) {
                continue;
            }

            $aliases = self::sidebarMatchVariants(
                $key,
                array_values(array_unique(array_merge(
                    self::aliasesFor($key),
                    $module['aliases'] ?? []
                ))),
                (string) ($module['title'] ?? '')
            );
            $matchLabels = array_values(array_filter($aliases, static function ($label) use ($labelOwners, $key): bool {
                $owners = array_keys($labelOwners[$label] ?? []);
                return $owners === [] || $owners === [$key];
            }));

            $rawPrefixes = $module['route_prefixes'] ?? [];
            if ($rawPrefixes === []) {
                $rawPrefixes = self::guessedRoutePrefixesFor($key, $aliases);
            }
            // Finance intentionally owns some legacy /accounting-module URLs.
            // When standalone Finance is enabled, controller-level middleware
            // resolves those pages correctly; do not hide them by URL in the
            // browser merely because core Accounting is disabled.
            if ($key === 'accounting_module' && self::isVisibleInSidebar('finance', $businessId)) {
                $rawPrefixes = array_values(array_filter($rawPrefixes, static function ($prefix): bool {
                    $normal = self::normalizeKey((string) $prefix);
                    return !in_array($normal, ['accounting_module', 'account', 'accounts'], true);
                }));
            }

            $prefixes = [];
            foreach ($rawPrefixes as $prefix) {
                $prefix = trim(strtolower((string) $prefix), '/ ');
                if ($prefix === '') {
                    continue;
                }
                $normal = self::normalizeKey($prefix);
                $owners = array_keys($prefixOwners[$normal] ?? []);
                if (count($owners) > 1) {
                    continue;
                }
                $prefixes[$prefix] = true;
            }

            $descriptors[] = [
                'key' => $key,
                'title' => $module['title'] ?? $key,
                'aliases' => $aliases,
                'match_labels' => $matchLabels,
                'route_prefixes' => array_keys($prefixes),
            ];
        }

        return self::$requestDisabledSidebarDescriptors[$businessId] = $descriptors;
    }

    /**
     * Disabled automatically discovered pages/tabs for the rendered sidebar
     * and tab guard. Missing keys intentionally stay enabled, so installing a
     * module or adding a new page never resets an existing business.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function disabledAutomaticPermissionDescriptorsForBusiness(
        ?int $businessId = null
    ): array {
        $businessId = $businessId ?: (int) session('user.business_id');
        if (empty($businessId)) {
            return [];
        }
        if (array_key_exists($businessId, self::$requestDisabledAutomaticPermissionDescriptors)) {
            return self::$requestDisabledAutomaticPermissionDescriptors[$businessId];
        }

        $details = self::activeSubscriptionPackageDetails($businessId);
        if (!is_array($details)) {
            return self::$requestDisabledAutomaticPermissionDescriptors[$businessId] = [];
        }

        $descriptors = [];
        foreach (AutomaticModuleRegistry::manageSections() as $section) {
            foreach ((array) ($section['items'] ?? []) as $item) {
                $type = (string) ($item['type'] ?? '');
                if (!in_array($type, ['page', 'tab'], true)) {
                    continue;
                }

                $permissionKey = self::normalizeKey((string) ($item['key'] ?? ''));
                if ($permissionKey === ''
                    || !array_key_exists($permissionKey, $details)
                    || self::isAutomaticPermissionEnabled($permissionKey, $businessId)) {
                    continue;
                }

                $descriptors[] = [
                    'key' => $permissionKey,
                    'module_key' => self::normalizeKey((string) ($section['module_key'] ?? '')),
                    'type' => $type,
                    // Automatic items already carry their authoritative route
                    // paths/selectors. Do not globally match common labels such
                    // as Dashboard or Settings across unrelated modules.
                    'match_labels' => [],
                    'route_paths' => array_values(array_unique(array_filter(array_map(
                        static fn ($path): string => trim((string) $path, '/ '),
                        (array) ($item['route_paths'] ?? [])
                    )))),
                    'selectors' => array_values(array_unique(array_filter(array_map(
                        'strval',
                        (array) ($item['selectors'] ?? [])
                    )))),
                ];
            }
        }

        /*
         * Hand-written Manage checkboxes pre-date automatic page discovery.
         * They still need one global sidebar rule. Exact disabled keys are
         * exposed as label descriptors, allowing legacy module sidebars to be
         * filtered without adding module-by-module JavaScript.
         */
        $known = array_fill_keys(array_map(
            [self::class, 'normalizeKey'],
            AutomaticModuleRegistry::explicitManagePermissionKeys()
        ), true);
        $descriptorKeys = array_fill_keys(array_map(
            static fn (array $descriptor): string => (string) ($descriptor['key'] ?? ''),
            $descriptors
        ), true);
        foreach ($details as $rawKey => $value) {
            $permissionKey = self::normalizeKey((string) $rawKey);
            if ($permissionKey === ''
                // One-word operational flags (for example cash or print) are
                // too broad for safe global text matching. Legacy menu/page
                // permissions are compound keys; module parents are already
                // enforced separately by Manage Side Bar.
                || !str_contains($permissionKey, '_')
                || !isset($known[$permissionKey])
                || isset($descriptorKeys[$permissionKey])
                || self::enabledFlag($value)) {
                continue;
            }

            $descriptors[] = [
                'key' => $permissionKey,
                'module_key' => '',
                'type' => 'explicit',
                'match_labels' => self::permissionMatchLabels($permissionKey),
                'route_paths' => [],
                'selectors' => [],
            ];
        }

        return self::$requestDisabledAutomaticPermissionDescriptors[$businessId] = $descriptors;
    }

    /**
     * Page/tab descriptors denied by the currently authenticated managed role.
     * Direct URL denial is handled by UserManagementNew middleware; these
     * descriptors keep the rendered sidebar and in-page tabs consistent.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function disabledManagedRolePermissionDescriptorsForCurrentUser(
        ?int $businessId = null
    ): array {
        try {
            if (!auth()->check() || self::hasSuperAdminBypass()) {
                return [];
            }

            $user = auth()->user();
            $businessId = $businessId
                ?: (int) session('business.id')
                ?: (int) session('user.business_id')
                ?: (int) ($user->business_id ?? 0);
            if ($businessId <= 0
                || $user->hasRole('Admin#' . $businessId)
                || !self::usesManagedRoleForCurrentUser($businessId)) {
                return [];
            }

            $descriptors = [];
            foreach (AutomaticModuleRegistry::manageSections() as $section) {
                $moduleKey = self::normalizeKey((string) ($section['module_key'] ?? ''));
                if ($moduleKey === ''
                    || !self::resolveConfiguredState($moduleKey, $businessId)
                    || !self::managedRoleAllowsPermission(
                        'umn.module.' . $moduleKey . '.view',
                        $businessId
                    )) {
                    continue;
                }

                foreach ((array) ($section['items'] ?? []) as $item) {
                    $type = strtolower((string) ($item['type'] ?? ''));
                    $permissionKey = self::normalizeKey((string) ($item['key'] ?? ''));
                    if (!in_array($type, ['page', 'tab'], true)
                        || $permissionKey === ''
                        || self::managedRoleAllowsPermission(
                            'umn.page.' . $permissionKey . '.view',
                            $businessId
                        )) {
                        continue;
                    }

                    $descriptors[] = [
                        'key' => $permissionKey,
                        'module_key' => $moduleKey,
                        'type' => $type,
                        // Role page/tab restrictions use discovered routes and
                        // selectors. Avoid hiding same-named pages elsewhere.
                        'match_labels' => [],
                        'route_paths' => array_values(array_unique(array_filter(array_map(
                            static fn ($path): string => trim((string) $path, '/ '),
                            (array) ($item['route_paths'] ?? [])
                        )))),
                        'selectors' => array_values(array_unique(array_filter(array_map(
                            'strval',
                            (array) ($item['selectors'] ?? [])
                        )))),
                    ];
                }
            }

            return $descriptors;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<int, string> */
    private static function permissionMatchLabels(string $key, string $label = ''): array
    {
        $normal = self::normalizeKey($key);
        $variants = [$normal, self::normalizeKey($label)];
        $variants[] = preg_replace('/(^|_)main_/', '$1', $normal);
        $variants[] = preg_replace('/^enable_/', '', $normal);
        $variants[] = preg_replace('/_(?:module|page)$/', '', $normal);

        foreach (array_values($variants) as $variant) {
            $listPosition = strpos((string) $variant, '_list_');
            if ($listPosition !== false) {
                // vat_main_list_vat_expense and vat_list_vat_expense both
                // render as "List VAT Expense" in historical sidebars.
                $variants[] = 'list_' . substr(
                    (string) $variant,
                    $listPosition + strlen('_list_')
                );
            }
        }

        // Older Manage controls commonly use singular keys while their menu
        // captions are plural (vat_expense => VAT Expenses). Generate only
        // final-token variants so unrelated page names cannot collide.
        $pluralPairs = [
            'account' => 'accounts',
            'contact' => 'contacts',
            'expense' => 'expenses',
            'invoice' => 'invoices',
            'payment' => 'payments',
            'product' => 'products',
            'report' => 'reports',
            'schedule' => 'schedules',
            'statement' => 'statements',
        ];
        foreach (array_values($variants) as $variant) {
            $variant = trim((string) $variant, '_ ');
            foreach ($pluralPairs as $singular => $plural) {
                if ($variant === $singular || str_ends_with($variant, '_' . $singular)) {
                    $variants[] = substr($variant, 0, -strlen($singular)) . $plural;
                }
                if ($variant === $plural || str_ends_with($variant, '_' . $plural)) {
                    $variants[] = substr($variant, 0, -strlen($plural)) . $singular;
                }
            }
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value, '_ '),
            $variants
        ))));
    }

    private static function enabledFlag($value): bool
    {
        if (is_array($value) || is_object($value)) {
            return true;
        }

        $flag = is_string($value) ? strtolower(trim($value)) : $value;

        return in_array($flag, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true);
    }
}
