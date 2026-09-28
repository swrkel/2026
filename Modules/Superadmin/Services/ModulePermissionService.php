<?php

namespace Modules\Superadmin\Services;

use App\Business;
use App\Services\AutomaticModuleRegistry;
use App\Utils\SidebarPermissionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModulePermissionService
{
    /**
     * One source of truth for master module/sidebar aliases.
     *
     * canonical => [display, aliases, parents, child prefixes, module_permission keys]
     */
    public function groups(): array
    {
        return [
            'petro' => [
                'display' => 'Petro',
                'aliases' => ['petro', 'petro_module', 'enable_petro_module', 'petro module', 'petro_module_enable'],
                'parents' => ['petro', 'petro_module', 'enable_petro_module', 'petro_module_enable'],
                'children_prefixes' => ['petro_', 'settlement_sw_', 'sw_', 'tank_', 'pump_', 'meter_'],
                'module_permissions' => ['petro', 'petro_module', 'enable_petro_module'],
            ],
            'petro_general' => [
                'display' => 'Petro General',
                'aliases' => ['petro_general', 'petro_general_module'],
                'parents' => ['petro_general', 'petro_general_module'],
                'children_prefixes' => ['petro_general_'],
                'module_permissions' => ['petro_general'],
            ],
            'petro_pd' => [
                'display' => 'Petro PD',
                'aliases' => ['petro_pd', 'petro pd', 'petro_p_d', 'petro p d', 'petro_pd_module', 'petro_p_d_module', 'petro pd module', 'pd_settlements', 'pd_operators', 'list_pd_settlements'],
                'parents' => ['petro_pd', 'petro_pd_module', 'petro_p_d', 'petro_p_d_module'],
                'children_prefixes' => ['petro_pd_', 'petro_p_d_', 'petropd_', 'pd_'],
                'module_permissions' => ['petro_pd', 'petro_pd_module', 'petro_p_d', 'petropd'],
            ],
            'petro_direct' => [
                'display' => 'Petro Direct',
                'aliases' => ['petro_direct', 'petro direct', 'petro_direct_module'],
                'parents' => ['petro_direct', 'petro_direct_module'],
                'children_prefixes' => ['petro_direct_'],
                'module_permissions' => ['petro_direct'],
            ],
            'daily_collection_sw' => [
                'display' => 'Daily Collection SW',
                'aliases' => ['daily_collection_sw', 'daily collection sw', 'dailycollectionsw', 'daily_collection_sw_module', 'dailycollectionsw_module', 'daily_collection_sw_enabled', 'DailyCollectionSW', 'daily_collection_sw_sub_menu', 'daily_collection_settings_daily_collection_sw'],
                'parents' => ['daily_collection_sw', 'daily_collection_sw_module', 'dailycollectionsw', 'dailycollectionsw_module', 'daily_collection_settings_daily_collection_sw'],
                'children_prefixes' => ['daily_collection_sw_', 'dailycollectionsw_', 'daily_collection_settings_'],
                'module_permissions' => ['daily_collection_sw', 'dailycollectionsw', 'daily_collection_settings'],
            ],
            'daily_collection' => [
                'display' => 'Daily Collection',
                'aliases' => ['daily_collection', 'daily collection', 'daily_collection_module', 'daily_collection_sub_menu', 'enable_petro_daily_collection'],
                'parents' => ['daily_collection', 'daily_collection_module', 'daily_collection_sub_menu', 'enable_petro_daily_collection'],
                'children_prefixes' => ['daily_collection_', 'dailycollection_'],
                'module_permissions' => ['daily_collection', 'dailycollection'],
            ],
            'products_new' => [
                'display' => 'Products New',
                'aliases' => ['products_new', 'products new', 'productsnew', 'products_new_module', 'productsnew_module'],
                'parents' => ['products_new', 'productsnew', 'products_new_module', 'productsnew_module'],
                'children_prefixes' => ['products_new_', 'productsnew_'],
                'module_permissions' => ['products_new', 'productsnew'],
            ],
            'finance' => [
                'display' => 'Finance',
                'aliases' => ['finance', 'finance_module', 'accounts_finance', 'accounts_finance_module'],
                'parents' => ['finance', 'finance_module', 'accounts_finance_module'],
                'children_prefixes' => ['finance_', 'accounts_finance_'],
                'module_permissions' => ['finance', 'accounts_finance'],
            ],
            'accounting_module' => [
                'display' => 'Accounting Module',
                'aliases' => ['accounting_module', 'access_account', 'account', 'accounts', 'accounting'],
                'parents' => ['accounting_module', 'access_account'],
                'children_prefixes' => ['accounting_', 'account_', 'fixed_assets', 'zero_previous_accounting_values'],
                'module_permissions' => ['accounting_module', 'access_account'],
            ],
            'contact_module' => [
                'display' => 'Contact Module',
                'aliases' => ['contact_module', 'contact', 'contacts'],
                'parents' => ['contact_module'],
                'children_prefixes' => ['contact_', 'customer_statement', 'customer_payment', 'outstanding_received', 'issue_payment_detail'],
                'module_permissions' => ['contact_module'],
            ],
            'mpcs' => [
                'display' => 'MPCS',
                'aliases' => ['mpcs', 'mpcs_module'],
                'parents' => ['mpcs', 'mpcs_module'],
                'children_prefixes' => ['mpcs_'],
                'module_permissions' => ['mpcs'],
            ],

            /*
             * Core ERP sidebar sections. These keys are intentionally distinct
             * from similarly named standalone modules. For example, the legacy
             * /purchases section is `purchases`, while Modules/Purchase is
             * `purchase`; /expenses is `expenses`, while Expenses-New is
             * `expenses_new`.
             */
            'purchases' => [
                'display' => 'Purchases (Core)',
                'aliases' => ['purchases', 'core_purchases'],
                'parents' => ['purchases'],
                'children_prefixes' => ['purchases_', 'purchase_return_', 'purchase_pos_', 'import_purchases_'],
                'module_permissions' => ['purchases'],
            ],
            'expenses' => [
                'display' => 'Expenses (Core)',
                'aliases' => ['expenses', 'core_expenses'],
                'parents' => ['expenses'],
                'children_prefixes' => ['expenses_', 'expense_categories_', 'expense_category_'],
                'module_permissions' => ['expenses'],
            ],
            'products' => [
                'display' => 'Products (Core)',
                'aliases' => ['products', 'core_products'],
                'parents' => ['products'],
                'children_prefixes' => ['products_', 'import_products_', 'variation_templates_', 'selling_price_group_', 'warranties_'],
                'module_permissions' => ['products'],
            ],
            'stock_transfer' => [
                'display' => 'Stock Transfer (Core)',
                'aliases' => ['stock_transfer', 'stock_transfers'],
                'parents' => ['stock_transfer'],
                'children_prefixes' => ['stock_transfer_', 'stock_transfers_'],
                'module_permissions' => ['stock_transfer'],
            ],
            'stock_adjustment' => [
                'display' => 'Stock Adjustment (Core)',
                'aliases' => ['stock_adjustment', 'stock_adjustments'],
                'parents' => ['stock_adjustment'],
                'children_prefixes' => ['stock_adjustment_', 'stock_adjustments_'],
                'module_permissions' => ['stock_adjustment'],
            ],
            'sales' => [
                'display' => 'Sales',
                'aliases' => ['sales', 'sale_module'],
                'parents' => ['sale_module'],
                'children_prefixes' => ['sales_', 'sell_', 'pos_', 'sell_return_', 'quotation_', 'shipment_'],
                'module_permissions' => ['sale_module'],
            ],
            'reports' => [
                'display' => 'Reports',
                'aliases' => ['reports', 'report_module'],
                'parents' => ['report_module'],
                'children_prefixes' => ['report_', 'reports_'],
                'module_permissions' => ['report_module'],
            ],
            'settings' => [
                'display' => 'Settings',
                'aliases' => ['settings', 'settings_module'],
                'parents' => ['settings_module'],
                'children_prefixes' => ['settings_', 'business_settings_', 'business_location_', 'invoice_scheme_', 'invoice_layout_', 'tax_rate_'],
                'module_permissions' => ['settings_module'],
            ],
            'user_management' => [
                'display' => 'User Management',
                'aliases' => ['user_management', 'user_management_module'],
                'parents' => ['user_management_module'],
                'children_prefixes' => ['user_', 'users_', 'role_', 'roles_', 'sales_commission_agent_'],
                'module_permissions' => ['user_management_module'],
            ],
            'pumper_dashboard' => [
                'display' => 'Pumper Dashboard',
                'aliases' => ['pumper_dashboard', 'pump_operator_dashboard'],
                'parents' => ['pump_operator_dashboard'],
                'children_prefixes' => ['pumper_dashboard_', 'pump_operator_'],
                'module_permissions' => ['pump_operator_dashboard'],
            ],
        ];
    }

    public function normaliseKey($key): string
    {
        $key = trim((string) $key);
        $key = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $key);
        $key = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', (string) $key);
        $key = strtolower((string) $key);
        $key = str_replace(['-', '.', '/', '\\'], '_', $key);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        return trim($key, '_');
    }

    public function compactKey($key): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower((string) $key));
    }

    public function canonicalKey($key): string
    {
        $normal = $this->normaliseKey($key);
        $compact = $this->compactKey($key);

        $automatic = AutomaticModuleRegistry::findSidebar($normal);
        if ($automatic) {
            return $automatic['key'];
        }

        foreach ($this->groups() as $canonical => $group) {
            $checks = array_merge([$canonical, $group['display'] ?? $canonical], $group['aliases'] ?? [], $group['parents'] ?? []);
            foreach ($checks as $check) {
                if ($normal === $this->normaliseKey($check) || $compact === $this->compactKey($check)) {
                    return $canonical;
                }
            }
        }

        // Common Laravel checkbox convention: foo_module should resolve to foo if no specific group exists.
        if (substr($normal, -7) === '_module') {
            return substr($normal, 0, -7);
        }

        return $normal;
    }

    public function decodeEnabledModules($enabledModules): array
    {
        $values = [];
        foreach ($this->decodeStoredModules($enabledModules) as $value) {
            if ($this->disabledMarkerCanonical((string) $value) === null) {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    private function decodeStoredModules($enabledModules): array
    {
        if (!is_array($enabledModules)) {
            if (empty($enabledModules) || !is_scalar($enabledModules)) {
                return [];
            }

            $decoded = json_decode((string) $enabledModules, true);
            $enabledModules = is_array($decoded) ? $decoded : [];
        }

        $values = [];
        $append = function ($items) use (&$append, &$values): void {
            foreach ((array) $items as $key => $value) {
                // Support both the normal string list and older associative
                // payloads such as {"suppliers_module": 1}. Ignore disabled
                // associative values instead of converting them to "0".
                if (!is_int($key) && (is_bool($value) || is_numeric($value) || is_string($value))) {
                    $flag = is_string($value) ? strtolower(trim($value)) : $value;
                    if (in_array($flag, [1, '1', true, 'true', 'yes', 'on'], true)) {
                        $text = trim((string) $key);
                        if ($text !== '') {
                            $values[] = $text;
                        }
                        continue;
                    }
                    if (in_array($flag, [0, '0', false, 'false', 'no', 'off', 'disabled', ''], true)) {
                        $canonical = $this->canonicalKey((string) $key);
                        if ($canonical !== '') {
                            $values[] = $this->disabledMarkerFor($canonical);
                        }
                        continue;
                    }
                }

                if (is_array($value)) {
                    $append($value);
                    continue;
                }

                if (!is_scalar($value)) {
                    continue;
                }

                $text = trim((string) $value);
                if ($text !== '') {
                    $values[] = $text;
                }
            }
        };

        $append($enabledModules);

        return array_values(array_unique($values));
    }

    public function enabledCanonicalMap(Business $business): array
    {
        $enabled = [];
        foreach ($this->decodeEnabledModules($business->enabled_modules ?? null) as $module) {
            $canonical = $this->canonicalKey($module);
            if ($canonical !== '') {
                $enabled[$canonical] = true;
            }
        }
        return $enabled;
    }

    public function isEnabled(Business $business, $moduleKey): bool
    {
        $canonical = $this->canonicalKey($moduleKey);

        // A business-level checkbox cannot make a standalone module usable
        // when Laravel Modules will not boot that module's provider/routes.
        // `false` means the module is installed but its exact module.json name
        // is missing/false in modules_statuses.json. Core/static sections
        // return null and continue through the normal business policy.
        if (AutomaticModuleRegistry::standaloneGlobalAvailability($canonical) === false) {
            return false;
        }

        $stored = $this->decodeStoredModules($business->enabled_modules ?? null);

        foreach ($stored as $value) {
            if ($this->disabledMarkerCanonical((string) $value) === $canonical) {
                return false;
            }
        }

        foreach ($stored as $value) {
            if ($this->canonicalKey($value) === $canonical) {
                return true;
            }
        }

        // Newly installed standalone modules and newly introduced core sidebar
        // sections are enabled by default until Super Admin explicitly unchecks
        // them. Once unchecked, the explicit disabled marker always wins.
        return AutomaticModuleRegistry::findSidebar($canonical) !== null
            || array_key_exists($canonical, $this->groups());
    }

    /**
     * Same parent module state used by every UI/control path.
     * Manage Side Bar is the parent source.  For old installations that still
     * have pre-service package_details, we fall back only for migration safety.
     */
    public function effectiveModuleEnabled(Business $business, $moduleKey, array $packageDetails = []): bool
    {
        $canonical = $this->canonicalKey($moduleKey);

        // Never let an older subscription/package flag resurrect an installed
        // standalone module whose provider/routes are globally unavailable.
        if (AutomaticModuleRegistry::standaloneGlobalAvailability($canonical) === false) {
            return false;
        }

        // An explicit Manage Side Bar disable must never be overridden by an
        // older package_details value.
        foreach ($this->decodeStoredModules($business->enabled_modules ?? null) as $value) {
            if ($this->disabledMarkerCanonical((string) $value) === $canonical) {
                return false;
            }
        }

        if ($this->isEnabled($business, $canonical)) {
            return true;
        }

        $group = $this->groups()[$canonical] ?? null;
        $keys = array_merge([$canonical, $canonical . '_module'], $group['parents'] ?? [], $group['aliases'] ?? [], $group['module_permissions'] ?? []);
        foreach ($keys as $key) {
            $normal = $this->normaliseKey($key);
            if ($normal !== '' && !empty($packageDetails[$normal])) {
                return true;
            }
        }

        return false;
    }

    public function storeEnabledModules(Business $business, array $selectedModules): array
    {
        [$storedEnabled, $storedDisabled] = $this->storedModuleStateMaps($business);

        $selected = [];
        foreach ($selectedModules as $module) {
            if (!is_scalar($module)) {
                continue;
            }
            $canonical = $this->canonicalKey($module);
            if ($canonical !== ''
                && AutomaticModuleRegistry::standaloneGlobalAvailability($canonical) !== false) {
                $selected[$canonical] = true;
            }
        }

        /*
         * The Manage Side Bar catalogue must also contain the modules that are
         * ALREADY switched off.
         *
         * decodeEnabledModules() strips every `__sidebar_disabled__:` marker,
         * so building the catalogue from it alone lost any module that is not a
         * hand-written group() entry and not auto-discovered by
         * AutomaticModuleRegistry (legacy sidebar values such as `tables`,
         * `booking`, `kitchen`, `add_sale`, `pos_sale`, `service_staff`,
         * `types_of_service`, `subscription`, ...). Those keys fell out of
         * $managedKeys, their marker was therefore not rewritten on the next
         * save, and the module reappeared in the Main System Sidebar.
         *
         * Including the disabled canonicals (and anything the form submitted)
         * keeps an unchecked module unchecked across every subsequent save.
         */
        $available = $this->discoverSidebarModules(array_merge(
            array_keys($storedEnabled),
            array_keys($storedDisabled),
            array_keys($selected)
        ));
        $managedKeys = array_keys($available);

        // Preserve values that are not represented by the current Manage Side
        // Bar screen, including disabled markers for globally unavailable
        // modules. If an administrator later deliberately enables that module
        // globally, the business's previous parent choice is still intact.
        $stored = [];
        foreach ($this->decodeStoredModules($business->enabled_modules ?? null) as $value) {
            $disabledCanonical = $this->disabledMarkerCanonical((string) $value);
            if ($disabledCanonical !== null) {
                if (!in_array($disabledCanonical, $managedKeys, true)) {
                    $stored[$this->disabledMarkerFor($disabledCanonical)] = true;
                }
                continue;
            }
            $canonical = $this->canonicalKey($value);
            if (!in_array($canonical, $managedKeys, true)) {
                $stored[$this->normaliseKey($value)] = true;
            }
        }

        foreach (array_keys($selected) as $canonical) {
            $stored[$canonical] = true;
            $stored[$canonical . '_module'] = true;

            $group = $this->groups()[$canonical] ?? null;
            if ($group) {
                foreach (array_merge($group['aliases'] ?? [], $group['parents'] ?? []) as $alias) {
                    $normal = $this->normaliseKey($alias);
                    if ($normal !== '') {
                        $stored[$normal] = true;
                    }
                }
            }

            $automatic = AutomaticModuleRegistry::findSidebar($canonical);
            foreach ($automatic['aliases'] ?? [] as $alias) {
                $normal = $this->normaliseKey($alias);
                if ($normal !== '') {
                    $stored[$normal] = true;
                }
            }
        }

        foreach ($managedKeys as $canonical) {
            if (!isset($selected[$canonical])) {
                $stored[$this->disabledMarkerFor($canonical)] = true;
            }
        }

        $values = array_keys($stored);
        sort($values, SORT_NATURAL | SORT_FLAG_CASE);
        $business->enabled_modules = $values;
        $business->save();

        SidebarPermissionUtil::forgetBusinessCache((int) $business->id);
        $this->syncSubscriptionsFromBusiness($business->fresh());

        return array_keys($selected);
    }

    public function discoverSidebarModules(array $enabledModules = []): array
    {
        $modules = [];
        $add = function ($key, $name = null) use (&$modules): void {
            $canonical = $this->canonicalKey($key);
            if ($canonical === ''
                || AutomaticModuleRegistry::standaloneGlobalAvailability($canonical) === false) {
                return;
            }
            if (!isset($modules[$canonical])) {
                $modules[$canonical] = $name ?: $this->displayName($canonical);
            }
        };

        foreach ($enabledModules as $key) {
            $add($key);
        }

        foreach ($this->groups() as $key => $group) {
            $add($key, $group['display'] ?? $this->displayName($key));
        }

        foreach (AutomaticModuleRegistry::sidebarModules() as $key => $module) {
            $add($key, $module['title'] ?? $this->displayName($key));
        }

        asort($modules, SORT_NATURAL | SORT_FLAG_CASE);

        return $modules;
    }

    public function sidebarModuleStates(Business $business, array $sidebarModules): array
    {
        // Decode once. Calling isEnabled() for every checkbox repeatedly parsed
        // the same JSON and walked the same values, which was noticeable with
        // 100+ installed modules.
        $enabled = [];
        $disabled = [];
        foreach ($this->decodeStoredModules($business->enabled_modules ?? null) as $value) {
            $marker = $this->disabledMarkerCanonical((string) $value);
            if ($marker !== null) {
                $disabled[$marker] = true;
                continue;
            }

            $canonical = $this->canonicalKey($value);
            if ($canonical !== '') {
                $enabled[$canonical] = true;
            }
        }

        $states = [];
        foreach (array_keys($sidebarModules) as $moduleKey) {
            $canonical = $this->canonicalKey($moduleKey);
            if (isset($disabled[$canonical])) {
                $states[$moduleKey] = false;
            } elseif (isset($enabled[$canonical])) {
                $states[$moduleKey] = true;
            } else {
                // Newly installed standalone modules and newly introduced core
                // sidebar sections are enabled by default until explicitly disabled.
                $states[$moduleKey] = AutomaticModuleRegistry::findSidebar($canonical) !== null
                    || array_key_exists($canonical, $this->groups());
            }
        }

        return $states;
    }

    /**
     * Discover standalone/new modules and their route-backed pages for the Manage page.
     * Existing saved values are never modified here; this only builds UI metadata.
     */
    public function discoverManageSections(): array
    {
        /*
         * MA-004 / regression fix: page permissions generated for the Role
         * screen must NOT appear on this form.
         *
         * MA-004 added 2,054 page permissions so a Business Admin could set
         * role permissions for every enabled module - previously only 7 of 119
         * modules offered any. The registry feeds BOTH the Role screen and this
         * Manage form, so those 2,054 also became 2,054 extra checkboxes here.
         *
         * This form posts every control as ONE json field, manage_form_payload.
         * Two thousand more entries pushed it past what the request could
         * carry, so it arrived truncated, json_decode failed, and saving died
         * with "The Manage form data could not be read."
         *
         * The generated items carry 'source' => 'module_pages'. They are
         * filtered out here and nowhere else, so:
         *   - this form keeps exactly the controls it had before MA-004
         *   - the Role screen still sees all 2,054, because
         *     BusinessPermissionBridge::sections() reads the registry directly
         *
         * The seven hand-written module_permissions.php files carry no marker,
         * so their curated entries still appear here as they always did.
         */
        $sections = AutomaticModuleRegistry::manageSections();

        foreach ($sections as $index => $section) {
            if (! is_array($section['items'] ?? null)) {
                continue;
            }

            $sections[$index]['items'] = array_values(array_filter(
                $section['items'],
                static fn ($item): bool => ($item['source'] ?? null) !== 'module_pages'
            ));
        }

        return array_values(array_filter(
            $sections,
            static fn (array $section): bool => ($section['items'] ?? []) !== []
        ));
    }

    /**
     * Keep Super Admin > Manage parent module switches and Manage Side Bar in
     * sync. Only parent controls that were actually submitted are changed;
     * unrelated sidebar selections and all child/page permissions are kept.
     *
     * This prevents a saved parent permission (for example Access Accounts /
     * Finance Module) from remaining blocked by an older disabled sidebar
     * marker and returning the module-disabled 403 page.
     *
     * @return array<string, bool> Canonical parent states changed by this save.
     */
    public function syncSidebarParentStatesFromManage(Business $business, Request $request, array $packageDetails = []): array
    {
        /*
         * FINAL HIERARCHY RULE (2026-09-09)
         *
         * Level 1 parent visibility belongs exclusively to Manage Side Bar.
         * Manage Page / Manage Page New may change child page/tab/feature values,
         * but must never add/remove business.enabled_modules or disabled markers.
         *
         * Older save paths still call this method. Keep the method as a deliberate
         * no-op so those callers remain binary-compatible without regaining parent
         * authority. enforceHierarchy() immediately re-mirrors the Level-1 state
         * into legacy package aliases where older runtime code still reads them.
         */
        return [];
    }

    /**
     * The standalone Purchase module uses the canonical key `purchase`, while
     * the original ERP package also uses `purchase` for the core Purchases
     * section. business.enabled_modules can distinguish `purchase` and
     * `purchases`, but package_details cannot. Never let the standalone module
     * overwrite the core package flag; use `purchase_module` and its page keys
     * for standalone Manage permissions instead.
     */
    private function isReservedCorePackageCollision(string $canonical, string $candidate): bool
    {
        return $this->canonicalKey($canonical) === 'purchase'
            && $this->normaliseKey($candidate) === 'purchase';
    }

    private function truthyFlag($value): bool
    {
        if (is_array($value)) {
            $value = end($value);
        }

        $flag = is_string($value) ? strtolower(trim($value)) : $value;

        return in_array($flag, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true);
    }

    public function applyAutoManagePermissions(array &$packageDetails, Request $request): void
    {
        /*
         * Persist every literal hand-written Manage checkbox through one
         * shared rule.  Previously many child/page controls were rendered and
         * accepted by the fast-save endpoint but the full Save path silently
         * omitted them from package_details.
         */
        $fullManageSubmission = is_string($request->input('manage_form_payload'))
            && trim((string) $request->input('manage_form_payload')) !== '';
        foreach (AutomaticModuleRegistry::explicitManageCheckboxKeys() as $key) {
            if ($request->exists($key)) {
                $packageDetails[$key] = $this->truthyFlag($request->input($key)) ? 1 : 0;
            } elseif ($fullManageSubmission) {
                // Browser FormData omits an unchecked checkbox. The compact
                // full-form marker lets us safely treat that absence as off;
                // partial/fast permission requests must leave other keys alone.
                $packageDetails[$key] = 0;
            }
        }

        $permissionMap = json_decode((string) $request->input('auto_manage_permissions_json', ''), true);
        if (is_array($permissionMap)) {
            foreach ($permissionMap as $key => $enabled) {
                $normal = $this->normaliseKey($key);
                if ($normal === '' || !preg_match('/^[a-z0-9_]+$/', $normal)) {
                    continue;
                }
                $packageDetails[$normal] = !empty($enabled) ? 1 : 0;
            }
            return;
        }

        // Backward compatibility for an already-open Manage page during deploy.
        $keys = $request->input('auto_manage_permission_keys', []);
        if (!is_array($keys)) {
            return;
        }

        foreach (array_values(array_unique($keys)) as $key) {
            $normal = $this->normaliseKey($key);
            if ($normal === '' || !preg_match('/^[a-z0-9_]+$/', $normal)) {
                continue;
            }
            $packageDetails[$normal] = (string) $request->input($key, '0') === '1' ? 1 : 0;
        }
    }

    /**
     * Parse an explicit disabled marker without re-canonicalising through the
     * standalone-module registry. That registry strips `_module` for unknown
     * keys, which previously changed `accounting_module` into `accounting` and
     * made the saved checkbox ineffective.
     */
    private function disabledMarkerCanonical(string $value): ?string
    {
        $prefix = AutomaticModuleRegistry::DISABLED_MARKER_PREFIX;
        if (!str_starts_with($value, $prefix)) {
            return null;
        }

        $canonical = $this->canonicalKey(substr($value, strlen($prefix)));

        return $canonical !== '' ? $canonical : null;
    }

    private function disabledMarkerFor(string $moduleKey): string
    {
        return AutomaticModuleRegistry::DISABLED_MARKER_PREFIX . $this->canonicalKey($moduleKey);
    }

    public function displayName($key): string
    {
        $canonical = $this->canonicalKey($key);
        $group = $this->groups()[$canonical] ?? null;
        if (!empty($group['display'])) {
            return $group['display'];
        }

        $text = preg_replace('/(?<!^)[A-Z]/', ' $0', (string) $key);
        $text = str_replace(['_', '-', '.'], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim(ucwords($text));
    }

    /**
     * Resolve stored module state in one linear pass.
     *
     * @return array{0: array<string, bool>, 1: array<string, bool>}
     */
    private function storedModuleStateMaps(Business $business): array
    {
        $enabled = [];
        $disabled = [];

        foreach ($this->decodeStoredModules($business->enabled_modules ?? null) as $value) {
            $marker = $this->disabledMarkerCanonical((string) $value);
            if ($marker !== null && $marker !== '') {
                $disabled[$marker] = true;
                continue;
            }

            $canonical = $this->canonicalKey($value);
            if ($canonical !== '') {
                $enabled[$canonical] = true;
            }
        }

        return [$enabled, $disabled];
    }

    /**
     * Hydrate Manage page parent checkboxes from one source only.
     *
     * Important: child/page/tab permissions are never reset here.  We only set
     * parent module aliases, so a code deployment or a newly discovered page
     * cannot change saved permissions.
     */
    public function syncManageModuleEnable(array &$manageModuleEnable, Business $business): void
    {
        // Build the enabled/disabled maps once. The previous implementation
        // decoded and canonicalised the entire stored module list again for
        // every module row (100+ times), which was one of the main Manage page
        // loading bottlenecks.
        [$enabledMap, $disabledMap] = $this->storedModuleStateMaps($business);
        $automaticModules = AutomaticModuleRegistry::sidebarModules();
        $groups = $this->groups();

        // A module that is switched off usually has ONLY its disabled marker
        // left in enabled_modules, so it is absent from $enabledMap. Feeding
        // just $enabledMap here skipped those rows entirely and the Manage page
        // kept showing them as enabled.
        $catalogue = $this->discoverSidebarModules(array_merge(
            array_keys($enabledMap),
            array_keys($disabledMap)
        ));

        foreach ($catalogue as $canonical => $name) {
            $isEnabled = !isset($disabledMap[$canonical])
                && (isset($enabledMap[$canonical])
                    || isset($automaticModules[$canonical])
                    || isset($groups[$canonical]));

            foreach ([$canonical, $canonical . '_module'] as $parentKey) {
                if (! $this->isReservedCorePackageCollision($canonical, $parentKey)) {
                    $manageModuleEnable[$parentKey] = $isEnabled ? 1 : 0;
                }
            }

            $group = $groups[$canonical] ?? null;
            foreach (array_merge($group['parents'] ?? [], $group['aliases'] ?? [], $group['module_permissions'] ?? []) as $key) {
                $normal = $this->normaliseKey($key);
                if ($normal !== '' && ! $this->isReservedCorePackageCollision($canonical, $normal)) {
                    $manageModuleEnable[$normal] = $isEnabled ? 1 : 0;
                }
            }

            $automatic = $automaticModules[$canonical] ?? null;
            foreach ($automatic['aliases'] ?? [] as $alias) {
                $normal = $this->normaliseKey($alias);
                if ($normal !== '' && ! $this->isReservedCorePackageCollision($canonical, $normal)) {
                    $manageModuleEnable[$normal] = $isEnabled ? 1 : 0;
                }
            }
        }
    }

    /**
     * Enforce parent-child hierarchy using the parent Manage Side Bar state.
     *
     * We deliberately do NOT clear child permissions when the parent is off.
     * A parent off state only makes the module ineffective/hidden.  Saved child
     * permissions remain in package_details so code updates do not destroy the
     * user's permission setup.
     */
    public function enforceHierarchy(array &$packageDetails, Business $business, ?Request $request = null): void
    {
        // Saving used to call isEnabled() once for every module. Each call
        // reparsed the complete enabled_modules payload, producing quadratic
        // work. Resolve the complete state once and reuse it for every row.
        [$enabledMap, $disabledMap] = $this->storedModuleStateMaps($business);
        $automaticModules = AutomaticModuleRegistry::sidebarModules();
        $groups = $this->groups();

        /*
         * Same problem as syncManageModuleEnable(): a disabled module lives in
         * enabled_modules only as `__sidebar_disabled__:<key>`, so it never
         * appeared in discoverSidebarModules(array_keys($enabledMap)).
         *
         * package_details[<key>] was therefore never written back as 0, and the
         * subscription package still reported the module as permitted - which is
         * what kept the disabled entry visible in the Main System Sidebar.
         */
        $catalogue = $this->discoverSidebarModules(array_merge(
            array_keys($enabledMap),
            array_keys($disabledMap)
        ));

        foreach ($catalogue as $canonical => $name) {
            $parentEnabled = !isset($disabledMap[$canonical])
                && (isset($enabledMap[$canonical])
                    || isset($automaticModules[$canonical])
                    || isset($groups[$canonical]));
            $keys = [$canonical, $canonical . '_module'];

            $group = $groups[$canonical] ?? null;
            if ($group) {
                $keys = array_merge($keys, $group['parents'] ?? [], $group['aliases'] ?? [], $group['module_permissions'] ?? []);
            }

            $automatic = $automaticModules[$canonical] ?? null;
            $keys = array_merge($keys, $automatic['aliases'] ?? []);

            foreach (array_unique($keys) as $key) {
                $normal = $this->normaliseKey($key);
                if ($normal !== '' && ! $this->isReservedCorePackageCollision($canonical, $normal)) {
                    $packageDetails[$normal] = $parentEnabled ? 1 : 0;
                }
            }
        }
    }

    public function canAccess(Business $business, $moduleKey): bool
    {
        return $this->isEnabled($business, $moduleKey);
    }

    public function isChildLocked(Business $business, $moduleKey): bool
    {
        return !$this->isEnabled($business, $moduleKey);
    }

    /**
     * After Manage Side Bar save, sync all subscriptions so Manage page reload state never disagrees.
     */
    public function syncSubscriptionsFromBusiness(Business $business): void
    {
        /*
         * Non-destructive sync.
         * Older versions rewrote package_details and could remove saved child permissions.
         * This version only updates stable parent module flags, so permissions do not change
         * when code is deployed, modules are renamed, or new pages/tabs are discovered.
         */
        try {
            /*
             * Follow the connection the Business model was resolved on rather
             * than the ambient default.
             *
             * A bare DB::table() uses Laravel's DEFAULT connection. Under
             * `tenant.context` that is a tenant database, so the subscriptions
             * written here could land in a different database from the business
             * row that was just saved - leaving enabled_modules and
             * package_details permanently disagreeing.
             *
             * $business->getConnection() is central when the caller resolved the
             * business through CentralContext, and correct in every other
             * context too, so the two writes can never separate.
             */
            $connection = $business->getConnection();

            /*
             * S717 / multi-tenant identity fix.
             *
             * Central business ids are not safe cross-database identities.  The
             * same numeric id can belong to a different company in another
             * tenant, and this installation already carries global_uid for the
             * stable cross-database mapping.  Manage Side Bar must therefore
             * update the subscription rows that belong to THIS central business
             * by global_uid whenever that identity is available.
             *
             * Do not fall back to business_id when both sides support global_uid
             * but no matching row exists: that is exactly how another company's
             * package flags can be overwritten.  The legacy business_id path is
             * retained only for schemas/rows that pre-date global_uid.
             */
            $subscriptionQuery = $connection->table('subscriptions');
            $businessGlobalUid = trim((string) ($business->global_uid ?? ''));
            $matchedByGlobalUid = false;

            try {
                if ($businessGlobalUid !== ''
                    && $connection->getSchemaBuilder()->hasColumn('subscriptions', 'global_uid')) {
                    $subscriptionQuery->where('global_uid', $businessGlobalUid);
                    $matchedByGlobalUid = true;
                }
            } catch (\Throwable $e) {
                // Schema probe failed; use the legacy key only when we cannot
                // establish that a global_uid mapping is available.
                $matchedByGlobalUid = false;
            }

            if (! $matchedByGlobalUid) {
                $subscriptionQuery->where('business_id', $business->id);
            }

            $rows = $subscriptionQuery->get(['id', 'package_details']);
            if ($matchedByGlobalUid && $rows->isEmpty()) {
                Log::warning('Manage Side Bar subscription sync skipped: no central subscription matched business global_uid.', [
                    'business_id' => (int) $business->id,
                    'global_uid' => $businessGlobalUid,
                ]);
            }

            foreach ($rows as $row) {
                $details = json_decode($row->package_details ?: '{}', true);
                if (!is_array($details)) {
                    $details = [];
                }
                $this->enforceHierarchy($details, $business, null);
                $connection->table('subscriptions')->where('id', $row->id)->update(['package_details' => json_encode($details)]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to sync stable parent module flags from Manage Side Bar.', [
                'business_id' => $business->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
