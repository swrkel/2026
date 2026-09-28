{{--
 |------------------------------------------------------------------------------
 | Role permission sections - driver
 |------------------------------------------------------------------------------
 |
 | Included by both role/create.blade.php and role/edit.blade.php. Walks the list
 | in role/_sections.php and renders each partial that passes its gates.
 |
 | THE TWO GATES, as required:
 |
 |   MANAGE          Superadmin -> All Business -> Manage writes the subscription's
 |                   package_details, which RoleController hands over as
 |                   $get_permissions. A section shows when ANY of its `package`
 |                   keys is set there. Sections with no `package` key are core
 |                   and always show, exactly as before.
 |
 |   MANAGE SIDE BAR SidebarPermissionUtil::isManageSidebarEnabled() is the same
 |                   check the sidebar itself uses, so the Role screen and the
 |                   sidebar can never disagree about what a business has.
 |
 | WHY THE SIDE BAR CHECK IS FAIL-OPEN
 |   isManageSidebarEnabled() returns FALSE for a key it does not recognise once a
 |   business has saved any Manage Side Bar rows. Applying it blindly to all 66
 |   package keys would therefore hide sections that Manage Side Bar has no
 |   opinion about. So the key is first put through canonicalModuleKey(): if it
 |   does not resolve to a real sidebar module, no side-bar gate is applied and
 |   the section behaves as it always has. Only modules Manage Side Bar actually
 |   controls are governed by it.
 |
 | Expects: $mode ('create' or 'edit'), $get_permissions, $role_permissions,
 |          $enabled_modules, and on edit $role and $users.
 --}}
@php
    $roleSections = include resource_path('views/role/_sections.php');

    /*
     * LA-1151: protect a role when a section is not rendered.
     *
     * The form posts the COMPLETE permission list, so a checkbox that is not
     * rendered is not posted and the permission is stripped on save. Gating
     * sections on Manage Side Bar therefore created a silent data-loss path:
     * turn Products off in Manage Side Bar, open a role that holds
     * product.view, press Update, and it is gone - with nothing on screen to
     * say so. That is the same fault the MA-002 review found between the
     * create and edit pages, and it must not come back through the gate.
     *
     * Every permission the role already holds whose section is skipped is
     * re-submitted as a hidden input. The section stays hidden, the role keeps
     * what it was given, and only an administrator who can SEE a checkbox can
     * change it.
     */
    $roleSectionPermissionMap = file_exists(resource_path('views/role/_section_permissions.php'))
        ? include resource_path('views/role/_section_permissions.php')
        : [];
    $roleSectionPreserved = [];

    $roleSectionEnabledModules = $enabled_modules ?? [];

    $roleSectionWhen = function ($when) use ($roleSectionEnabledModules, $mode) {
        switch ($when) {
            case 'service_staff':
                return in_array('service_staff', $roleSectionEnabledModules);
            case 'tables_and_service_staff':
                return in_array('tables', $roleSectionEnabledModules)
                    && in_array('service_staff', $roleSectionEnabledModules);
            case 'has_users':
                return !empty($users) && count($users) > 0;
            case 'customers_module_enabled':
                /*
                 * LA-1151: the standalone Customers module has no key in
                 * Superadmin's Manage list, so there is no package flag to test.
                 * Its own service is the authority - the same one the Customers
                 * sidebar section uses. Fail open, so a resolution problem never
                 * hides the section and leaves the permissions ungrantable.
                 */
                try {
                    if (class_exists(\Modules\Customers\Services\CustomerPermissionService::class)) {
                        return (bool) app(\Modules\Customers\Services\CustomerPermissionService::class)->moduleEnabled();
                    }
                } catch (\Throwable $e) {
                    return true;
                }

                return true;
        }

        return true;
    };

    $roleSectionSidebarAllowsOne = function ($key) {
        if (empty($key) || ! class_exists(\App\Utils\SidebarPermissionUtil::class)) {
            return true;
        }

        try {
            // Unrecognised key: Manage Side Bar has no opinion, so do not gate.
            if (\App\Utils\SidebarPermissionUtil::canonicalModuleKey($key) === '') {
                return true;
            }

            return \App\Utils\SidebarPermissionUtil::isManageSidebarEnabled($key);
        } catch (\Throwable $e) {
            return true;
        }
    };

    $roleSectionSidebarAllows = function ($key) use ($roleSectionSidebarAllowsOne) {
        /*
         * S678-RETIRE: $key may now be an ARRAY.
         *
         * A section whose permissions are shared by several modules must show
         * when ANY of them is enabled. Section 56 is the case that forced
         * this: its 43 permissions are enforced by PetroPD, PetroGeneral,
         * PetroDirect, PumperDashboard, EVCharging and core, so gating it on
         * Petro alone made all of them ungrantable once Petro was retired.
         *
         * A single string behaves exactly as before.
         */
        if (is_array($key)) {
            if (empty($key)) {
                return true;
            }

            foreach ($key as $singleKey) {
                if ($roleSectionSidebarAllowsOne($singleKey)) {
                    return true;
                }
            }

            return false;
        }

        return $roleSectionSidebarAllowsOne($key);
    };

@endphp

@foreach ($roleSections as $roleSection)
    @php
        $show = true;

        if (!empty($roleSection['mode']) && $roleSection['mode'] !== $mode) {
            $show = false;
        }

        if ($show && !empty($roleSection['package'])) {
            $show = false;
            foreach ($roleSection['package'] as $packageKey) {
                if (!empty($get_permissions[$packageKey])) {
                    $show = true;
                    break;
                }
            }
        }

        if ($show && !empty($roleSection['when'])) {
            $show = $roleSectionWhen($roleSection['when']);
        }

        if ($show && !empty($roleSection['sidebar'])) {
            $show = $roleSectionSidebarAllows($roleSection['sidebar']);
        }
    @endphp

    @if ($show)
        {{-- S651: @includeIf, not @include.

             A deployment that copies _sections.php without every file in
             sections/ made the whole Roles screen fail with
             "View [role.sections.47_products_new] not found" - one missing
             partial took down Add Role entirely.

             The manifest and the folder should always ship together, but a
             partial copy must degrade to a missing SECTION, not a dead PAGE.
             The warning below records which file is absent so it is fixable
             rather than silent. --}}
        @includeIf('role.sections.' . $roleSection['view'])

        @php
            if (! view()->exists('role.sections.' . $roleSection['view'])) {
                \Illuminate\Support\Facades\Log::warning(
                    'Role screen: a permission section is missing from this deployment.',
                    ['view' => 'role.sections.' . $roleSection['view']]
                );
            }
        @endphp
    @else
        @php
            /*
             * Skipped: keep whatever this role already holds from this section.
             */
            foreach (($roleSectionPermissionMap[$roleSection['view']] ?? []) as $skippedPermission) {
                if (in_array($skippedPermission, $role_permissions ?? [], true)
                    && ! in_array($skippedPermission, $roleSectionPreserved, true)) {
                    $roleSectionPreserved[] = $skippedPermission;
                }
            }
        @endphp
    @endif
@endforeach

@foreach ($roleSectionPreserved as $preservedPermission)
    <input type="hidden" name="permissions[]" value="{{ $preservedPermission }}">
@endforeach
