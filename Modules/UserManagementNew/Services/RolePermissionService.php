<?php

namespace Modules\UserManagementNew\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionService
{
    public const PREFIX = 'umn.';
    public const MANAGED = 'umn.managed';

    private const STOCK_REPORTS_MODULE = 'stock_reports';
    private const STOCK_REPORT_VIEW = 'stock_report.view';

    public function rights(): array
    {
        return [
            'view' => ['label' => 'View', 'icon' => 'fa-eye'],
            'edit' => ['label' => 'Edit / Add', 'icon' => 'fa-pencil'],
            'delete' => ['label' => 'Delete', 'icon' => 'fa-trash'],
            'print' => ['label' => 'Print', 'icon' => 'fa-print'],
            'pdf' => ['label' => 'PDF', 'icon' => 'fa-file-pdf-o'],
            'email' => ['label' => 'Email', 'icon' => 'fa-envelope'],
            'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'fa-whatsapp'],
        ];
    }

    public function modulePermission(string $moduleKey, string $right): string
    {
        return self::PREFIX . 'module.' . $this->key($moduleKey) . '.' . $this->key($right);
    }

    public function pagePermission(string $pageKey): string
    {
        return self::PREFIX . 'page.' . $this->key($pageKey) . '.view';
    }

    public function catalogue(array $sections): array
    {
        $allowed = [self::MANAGED => true];
        foreach ($sections as $section) {
            foreach (array_keys($this->rights()) as $right) {
                $allowed[$this->modulePermission((string) $section['module_key'], $right)] = true;
            }
            foreach ((array) ($section['items'] ?? []) as $item) {
                $allowed[$this->pagePermission((string) ($item['key'] ?? ''))] = true;
            }
        }

        return array_keys($allowed);
    }

    public function selected(Role $role): array
    {
        $names = $role->permissions()->pluck('name')->all();
        $selected = array_values(array_filter($names, static fn ($name): bool =>
            str_starts_with((string) $name, self::PREFIX)
        ));

        // Stock Reports used stock_report.view before UserManagementNew existed.
        // Mirror that established permission into the managed module View right
        // when displaying an older role, so the Role form never shows a false
        // "off" state for a role that already has Stock Reports access.
        if (in_array(self::STOCK_REPORT_VIEW, $names, true)) {
            $selected[] = $this->modulePermission(self::STOCK_REPORTS_MODULE, 'view');
        }

        return array_values(array_unique($selected));
    }

    public function sanitize(array $submitted, array $sections): array
    {
        $allowed = array_fill_keys($this->catalogue($sections), true);
        $selected = [self::MANAGED];

        foreach ($submitted as $permission) {
            $permission = trim((string) $permission);
            if ($permission !== '' && isset($allowed[$permission])) {
                $selected[] = $permission;
            }
        }

        return array_values(array_unique($selected));
    }

    /**
     * IS2347: when one or more page/tab permissions are selected, derive the
     * parent module View permission. This keeps the hierarchy intuitive:
     * selecting one page shows the module parent plus that page - it does not
     * expose the module's other pages and does not grant Edit/Delete/etc.
     *
     * @param array<int,string> $selected
     * @param array<int,array<string,mixed>> $sections
     * @return array<int,string>
     */
    public function ensureParentModuleViewForSelectedPages(array $selected, array $sections): array
    {
        $set = array_fill_keys($selected, true);

        foreach ($sections as $section) {
            $moduleKey = trim((string) ($section['module_key'] ?? ''));
            if ($moduleKey === '') {
                continue;
            }

            foreach ((array) ($section['items'] ?? []) as $item) {
                $pageKey = trim((string) ($item['key'] ?? ''));
                if ($pageKey === '') {
                    continue;
                }

                if (isset($set[$this->pagePermission($pageKey)])) {
                    $set[$this->modulePermission($moduleKey, 'view')] = true;
                    break;
                }
            }
        }

        $set[self::MANAGED] = true;

        return array_keys($set);
    }

    /*
     |--------------------------------------------------------------------------
     | S640/S642: the application permissions a Managed role may grant.
     |--------------------------------------------------------------------------
     |
     | The umn.* permissions this service creates are RESTRICTIVE ONLY - the
     | sidebar and EnforceManagedRolePermissions use them to hide and deny. They
     | grant nothing. The application authorises on its own permission names
     | (purchase.view, account.access, product.view and ~600 others), so a role
     | created here held umn.* and nothing else, and the user could do nothing.
     |
     | The Role form now also renders the real permission sections, and this
     | filters what comes back. The whitelist is
     | resources/views/role/_section_permissions.php - the generated map of every
     | permission the Role screen actually offers. Anything not on it is dropped,
     | so a hand-crafted request cannot grant an arbitrary permission name.
     */
    public function allowedApplicationPermissions(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $path = resource_path('views/role/_section_permissions.php');

        if (! is_file($path)) {
            return $cache = [];
        }

        $map = include $path;
        $flat = [];

        foreach ((array) $map as $section => $permissions) {
            foreach ((array) $permissions as $permission) {
                /*
                 * IS2347: the Purchase permission group is intentionally limited
                 * to the nine Purchase permissions requested by the business.
                 * product.edit_sku belongs to product maintenance and must not be
                 * grantable from Purchase on the User Management New role form.
                 */
                if ($section === '23_purchase' && $permission === 'product.edit_sku') {
                    continue;
                }
                $flat[$permission] = true;
            }
        }

        return $cache = $flat;
    }

    /**
     * Keep only the submitted names that the Role screen legitimately offers.
     */
    public function sanitizeApplicationPermissions(array $submitted): array
    {
        $allowed = $this->allowedApplicationPermissions();

        if (empty($allowed)) {
            return [];
        }

        $selected = [];

        foreach ($submitted as $permission) {
            $permission = trim((string) $permission);

            if ($permission !== '' && isset($allowed[$permission])) {
                $selected[$permission] = $permission;
            }
        }

        return array_values($selected);
    }

    /**
     * @param array|null $applicationPermissions null = the form did not render the
     *                                           application sections, so whatever the
     *                                           role already holds must be preserved
     *                                           rather than wiped.
     */
    public function sync(Role $role, array $selected, ?array $applicationPermissions = null): void
    {
        /*
         * S640/S642: when the form DID render the application sections, the
         * submitted set replaces what the role holds - that is what makes a
         * permission removable. When it did not (an older cached form, or a
         * caller that has not been updated), the existing set is preserved.
         *
         * Without that distinction, saving from a form that does not render the
         * sections would silently strip every application permission from the
         * role - the same data loss the MA-002 review found on the legacy Role
         * screen, and guarded against again in parcel 2-31.
         */
        $legacy = $applicationPermissions !== null
            ? $applicationPermissions
            : $role->permissions()
                ->where('name', 'not like', self::PREFIX . '%')
                ->pluck('name')
                ->all();

        /*
         * Stock Reports has one established application permission:
         * stock_report.view. The managed module View right and that historical
         * permission represent the same access and must never drift apart.
         *
         * - an older managed role that already has stock_report.view is upgraded
         *   to umn.module.stock_reports.view;
         * - selecting Stock Reports -> View in UserManagementNew derives
         *   stock_report.view for the StockReports sidebar and any legacy checks.
         */
        $selected = $this->normalizeManagedSelectionsFromApplication($selected, $legacy);

        /*
         * S715: managed module rights must also satisfy the permissions that
         * the owning module itself checks with auth()->can().
         *
         * UserManagementNew's umn.* permissions are the authoritative module /
         * page / action contract, but older modules still protect their own
         * routes and sidebar items with historical Spatie permission names.
         * Petro PD is one of them (petro_pd.access, petro_pd.create_settlement,
         * petro_pd.view_operators, ...). Without this bridge a role can show
         * Petro PD as selected here while Petro PD itself still sees no access.
         *
         * Compatibility permissions are DERIVED from the managed selections;
         * they are not a second independent source of truth. This also removes
         * stale Petro PD compatibility grants when the corresponding managed
         * right/page is unticked.
         */
        $legacy = $this->applyManagedModuleCompatibility($legacy, $selected);

        /*
         * S683: the role MUST carry the umn.managed marker.
         *
         * Reported: a role was given Petro PD rights, but the user who held it
         * saw only Finance.
         *
         * SidebarPermissionUtil decides what a user may see by collecting the
         * `umn.` permissions on their roles - but it SKIPS any role that does not
         * also hold `umn.managed`:
         *
         *     if (!in_array('umn.managed', $names, true)) { continue; }
         *
         * That marker is what tells the sidebar "this role's module rights are
         * managed here, honour them". Without it the role is passed over
         * entirely, every module grant on it is ignored, and the user falls back
         * to whatever legacy access remains - which is why only Finance showed.
         *
         * The constant existed (self::MANAGED) and was included when BUILDING a
         * selection, but nothing guaranteed it survived to the save: a form that
         * did not submit it, or a selection assembled anywhere else, produced a
         * role the sidebar could not read.
         *
         * It is now added unconditionally at the point of writing, so the marker
         * cannot be lost however the selection was assembled.
         */
        $selected[] = self::MANAGED;

        $all = array_values(array_unique(array_merge($legacy, $selected)));

        /*
         * Performance:
         * Spatie syncPermissions() detaches every role permission and then
         * re-attaches the complete set. On this large Role form that makes
         * every Save unnecessarily expensive.
         *
         * Resolve the desired permission IDs once and let the underlying
         * belongsToMany relation write only the actual differences.
         */
        $permissionRows = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $all)
            ->get(['id', 'name']);

        $existingNames = $permissionRows->pluck('name')->all();
        $missing = array_values(array_diff($all, $existingNames));

        foreach ($missing as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        if ($missing !== []) {
            $permissionRows = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $all)
                ->get(['id', 'name']);
        }

        $permissionIds = $permissionRows->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        $permissionTable = (new Permission())->getTable();

        $currentPermissionIds = $role->permissions()
            ->pluck($permissionTable . '.id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        sort($permissionIds);
        sort($currentPermissionIds);

        if ($permissionIds !== $currentPermissionIds) {
            $role->permissions()->sync($permissionIds);
            $role->unsetRelation('permissions');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    /**
     * S715: repair an already-assigned managed role on first request.
     *
     * Earlier builds could save umn.module.* / umn.page.* permissions without
     * the umn.managed marker, and roles saved before the compatibility bridge
     * obviously do not contain the Petro PD permissions derived below. Waiting
     * for an administrator to open and re-save every role leaves existing users
     * broken, so the module heals only the affected business roles lazily.
     *
     * The method is intentionally conservative: a role with no umn.* contract
     * is a legacy role and is never changed.
     */
    public function repairAssignedManagedRoles($user, int $businessId): bool
    {
        if (!$user || $businessId <= 0 || !method_exists($user, 'roles')) {
            return false;
        }

        $changed = false;

        try {
            $roles = $user->roles()
                ->where('roles.business_id', $businessId)
                ->where('roles.guard_name', 'web')
                ->with('permissions')
                ->get();

            foreach ($roles as $role) {
                $names = $role->permissions
                    ->pluck('name')
                    ->map(static fn ($name): string => trim((string) $name))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $managed = array_values(array_filter($names, static fn (string $name): bool =>
                    str_starts_with($name, self::PREFIX)
                ));

                // No UserManagementNew contract = leave the legacy role alone.
                if ($managed === []) {
                    continue;
                }

                $hasRealManagedSelection = false;
                foreach ($managed as $name) {
                    if ($name !== self::MANAGED) {
                        $hasRealManagedSelection = true;
                        break;
                    }
                }
                if (!$hasRealManagedSelection && !in_array(self::MANAGED, $managed, true)) {
                    continue;
                }

                if (!in_array(self::MANAGED, $managed, true)) {
                    $managed[] = self::MANAGED;
                }

                $application = array_values(array_filter($names, static fn (string $name): bool =>
                    !str_starts_with($name, self::PREFIX)
                ));

                // Upgrade either side of the old/new Stock Reports permission
                // pair before deriving compatibility permissions. This repairs
                // existing managed roles without granting access to roles that
                // had neither permission.
                $managed = $this->normalizeManagedSelectionsFromApplication($managed, $application);
                $application = $this->applyManagedModuleCompatibility($application, $managed);

                $desired = array_values(array_unique(array_merge($managed, $application)));
                $current = $names;
                sort($desired);
                sort($current);

                if ($desired === $current) {
                    continue;
                }

                $existing = Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', $desired)
                    ->pluck('name')
                    ->all();

                foreach (array_diff($desired, $existing) as $name) {
                    Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                }

                $role->syncPermissions($desired);
                $role->unsetRelation('permissions');
                $changed = true;
            }

            if ($changed) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                if (class_exists(\App\Utils\SidebarPermissionUtil::class)
                    && method_exists(\App\Utils\SidebarPermissionUtil::class, 'forgetBusinessCache')) {
                    \App\Utils\SidebarPermissionUtil::forgetBusinessCache($businessId);
                }

                if (method_exists($user, 'unsetRelation')) {
                    $user->unsetRelation('roles');
                    $user->unsetRelation('permissions');
                }
            }
        } catch (\Throwable $e) {
            // A repair problem must never make an otherwise valid login fail.
            report($e);
        }

        return $changed;
    }

    /**
     * Keep legacy Petro PD permission checks aligned with UserManagementNew.
     *
     * @param array<int,string> $applicationPermissions
     * @param array<int,string> $managedPermissions
     * @return array<int,string>
     */
    private function applyManagedModuleCompatibility(
        array $applicationPermissions,
        array $managedPermissions
    ): array {
        $application = array_fill_keys(array_values(array_filter(array_map(
            static fn ($name): string => trim((string) $name),
            $applicationPermissions
        ))), true);

        $managed = array_fill_keys(array_values(array_filter(array_map(
            static fn ($name): string => trim((string) $name),
            $managedPermissions
        ))), true);

        // Petro PD's historical permissions are derived, never authoritative.
        foreach ($this->allPetroPdCompatibilityPermissions() as $permission) {
            unset($application[$permission]);
        }

        foreach ($this->petroPdCompatibilityPermissions($managed) as $permission) {
            $application[$permission] = true;
        }

        // Stock Reports: the UserManagementNew module View right is the managed
        // source of truth. Keep the historical application permission in lockstep
        // because the StockReports module and older code still check it directly.
        unset($application[self::STOCK_REPORT_VIEW]);
        if (isset($managed[$this->modulePermission(self::STOCK_REPORTS_MODULE, 'view')])) {
            $application[self::STOCK_REPORT_VIEW] = true;
        }

        // S759: Standalone Suppliers is still protected by core/global route
        // middleware that authorises against supplier.* / suppliers.* names.
        // Derive those application permissions from the managed Suppliers rights
        // so a role enabled in User Management New is not rejected with a 403
        // before the Suppliers module middleware can run.
        foreach ($this->supplierCompatibilityPermissions($managed) as $permission) {
            $application[$permission] = true;
        }

        return array_keys($application);
    }

    /**
     * Application permission aliases required by the standalone Suppliers module.
     * Module View is required before any edit/delete aliases are derived.
     *
     * @param array<string,bool> $managed
     * @return array<int,string>
     */
    private function supplierCompatibilityPermissions(array $managed): array
    {
        $module = 'suppliers';
        $view = isset($managed[$this->modulePermission($module, 'view')]);
        if (!$view) {
            return [];
        }

        $out = [
            'supplier.view',
            'suppliers.view',
        ];

        if (isset($managed[$this->modulePermission($module, 'edit')])) {
            $out = array_merge($out, [
                'supplier.create',
                'supplier.update',
                'supplier.edit',
                'suppliers.create',
                'suppliers.edit',
            ]);
        }

        if (isset($managed[$this->modulePermission($module, 'delete')])) {
            $out = array_merge($out, [
                'supplier.delete',
                'suppliers.delete',
            ]);
        }

        return array_values(array_unique($out));
    }

    /**
     * Backfill the managed Stock Reports View right from an existing historical
     * stock_report.view grant. This is deliberately one narrow compatibility
     * bridge; it never grants Stock Reports to a role that had no Stock Reports
     * permission at all.
     *
     * @param array<int,string> $managedPermissions
     * @param array<int,string> $applicationPermissions
     * @return array<int,string>
     */
    private function normalizeManagedSelectionsFromApplication(
        array $managedPermissions,
        array $applicationPermissions
    ): array {
        $application = array_fill_keys(array_values(array_filter(array_map(
            static fn ($name): string => trim((string) $name),
            $applicationPermissions
        ))), true);

        if (isset($application[self::STOCK_REPORT_VIEW])) {
            $managedPermissions[] = $this->modulePermission(self::STOCK_REPORTS_MODULE, 'view');
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($name): string => trim((string) $name),
            $managedPermissions
        ))));
    }

    /**
     * @param array<string,bool> $managed
     * @return array<int,string>
     */
    private function petroPdCompatibilityPermissions(array $managed): array
    {
        $module = 'petro_pd';
        $view = isset($managed[$this->modulePermission($module, 'view')]);
        if (!$view) {
            return [];
        }

        $out = ['petro_pd.access'];

        $edit = isset($managed[$this->modulePermission($module, 'edit')]);
        $delete = isset($managed[$this->modulePermission($module, 'delete')]);
        $whatsapp = isset($managed[$this->modulePermission($module, 'whatsapp')]);

        if ($edit) {
            $out = array_merge($out, [
                'petro_pd.create_settlement',
                'petro_pd.edit_settlement',
                'petro_pd.manual_entry',
                'petro_pd.meter_sale_tab',
                'petro_pd.other_sale_tab',
                'petro_pd.other_income_tab',
                'petro_pd.customer_payment_tab',
                'petro_pd.payment_tab',
                'petro_pd.request_amount_adjustment',
                'petro_pd.adjust_settlement_amounts',
            ]);
        }

        if ($delete) {
            $out[] = 'petro_pd.delete_settlement';
        }

        if ($whatsapp) {
            $out[] = 'petro_pd_whatsapp';
        }

        // Page-specific view permissions. The managed page checkbox remains
        // authoritative: a module View right alone must not open every report.
        if ($this->managedHasPage($managed, [
            'petropd_pd_operators',
            'petropd_pd_operators_',
            'petropd_pump_operators_',
            'petropd_pump_operator_',
            'petropd_day_entries',
            'petropd_day_entry_',
            'petropd_recover_shortage_create',
            'petropd_excess_comission_create',
            'petropd_pump_assignments_',
        ])) {
            $out[] = 'petro_pd.view_operators';
        }

        if ($this->managedHasPage($managed, ['petropd_user_activity_report'])) {
            $out[] = 'petro_pd.view_report';
        }

        if ($this->managedHasPage($managed, ['petropd_adjusted_amounts_report'])) {
            $out[] = 'petro_pd.view_adjusted_amounts_report';
        }

        if ($this->managedHasPage($managed, ['petropd_payment_reconciliation_report'])) {
            $out[] = 'petro_pd.view_payment_reconciliation_report';
        }

        if ($this->managedHasPage($managed, ['petropd_list_pd_settlement'])) {
            $out[] = 'petro_pd.list_settlement';
        }

        if ($this->managedHasPage($managed, ['petropd_sms_notifications'])) {
            $out[] = 'petro_pd_sms_notifications';
        }

        if ($edit && $this->managedHasPage($managed, ['petropd_pump_assignments_'])) {
            $out[] = 'bulk_assign_pumps';
        }

        if ($this->managedHasPage($managed, ['petropd_day_end_settlement'])) {
            if ($edit) {
                $out[] = 'add_day_end_settlement';
                $out[] = 'edit_day_end_settlement';
                $out[] = 'petro_pd_day_end_settlement.edit';
            }
            if ($delete) {
                $out[] = 'petro_pd_day_end_settlement.delete';
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * True when at least one selected managed page equals or starts with one of
     * the supplied Petro PD page keys/prefixes.
     *
     * @param array<string,bool> $managed
     * @param array<int,string> $needles
     */
    private function managedHasPage(array $managed, array $needles): bool
    {
        $prefix = self::PREFIX . 'page.';

        foreach (array_keys($managed) as $permission) {
            if (!str_starts_with($permission, $prefix) || !str_ends_with($permission, '.view')) {
                continue;
            }

            $page = substr($permission, strlen($prefix), -5);
            foreach ($needles as $needle) {
                if ($page === $needle || str_starts_with($page, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<int,string> */
    private function allPetroPdCompatibilityPermissions(): array
    {
        return [
            'petro_pd.access',
            'petro_pd.create_settlement',
            'petro_pd.edit_settlement',
            'petro_pd.delete_settlement',
            'petro_pd.manual_entry',
            'petro_pd.meter_sale_tab',
            'petro_pd.other_sale_tab',
            'petro_pd.other_income_tab',
            'petro_pd.customer_payment_tab',
            'petro_pd.payment_tab',
            'petro_pd.view_operators',
            'petro_pd.view_report',
            'petro_pd.view_adjusted_amounts_report',
            'petro_pd.view_payment_reconciliation_report',
            'petro_pd.list_settlement',
            'petro_pd_sms_notifications',
            'petro_pd_whatsapp',
            'petro_pd.request_amount_adjustment',
            'petro_pd.adjust_settlement_amounts',
            'bulk_assign_pumps',
            'add_day_end_settlement',
            'edit_day_end_settlement',
            'petro_pd_day_end_settlement.edit',
            'petro_pd_day_end_settlement.delete',
        ];
    }

    private function key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($value)), '_');
    }
}
