@php
    $__umnSidebarVisible = false;
    $__umnSidebarActive = request()->routeIs('user-management-new.*')
        || request()->is('user-management-new')
        || request()->is('user-management-new/*');

    try {
        $__umnUser = auth()->user();

        $__umnBusinessId = (int) (
            session('business.id')
            ?: session('user.business_id')
            ?: ($__umnUser->business_id ?? 0)
        );

        $__umnPrivileged = $__umnUser && (
            \App\Utils\SidebarPermissionUtil::hasSuperAdminBypass()
            || ($__umnBusinessId > 0 && $__umnUser->hasRole('Admin#' . $__umnBusinessId))
        );

        // S754: for roles managed by UserManagementNew the umn.* contract is
        // authoritative. Legacy application permissions must not make this
        // parent or its child pages visible when the managed role did not grant
        // them.
        $__umnPermissionService = app(\Modules\UserManagementNew\Services\RolePermissionService::class);
        $__umnManagedRole = $__umnUser
            && $__umnBusinessId > 0
            && $__umnPermissionService->userUsesManagedRole($__umnUser, $__umnBusinessId);
        $__umnManagedModuleView = $__umnPrivileged || !$__umnManagedRole
            || $__umnPermissionService->managedUserAllows(
                $__umnUser,
                $__umnBusinessId,
                $__umnPermissionService->modulePermission('user_management_new', 'view')
            );
        $__umnManagedRolesPage = $__umnPrivileged || !$__umnManagedRole
            || $__umnPermissionService->managedUserAllows(
                $__umnUser,
                $__umnBusinessId,
                $__umnPermissionService->pagePermission('user_management_new_roles')
            );
        $__umnManagedUsersPage = $__umnPrivileged || !$__umnManagedRole
            || $__umnPermissionService->managedUserAllows(
                $__umnUser,
                $__umnBusinessId,
                $__umnPermissionService->pagePermission('user_management_new_users')
            );

        $__umnAllows = function (string $permission) use ($__umnUser, $__umnBusinessId): bool {
            if (!$__umnUser) {
                return false;
            }

            if (method_exists($__umnUser, 'roleAllowsPermission')) {
                return $__umnUser->roleAllowsPermission($permission, $__umnBusinessId);
            }

            return $__umnUser->can($permission);
        };

        $__umnCanRoles = $__umnUser
            && \Illuminate\Support\Facades\Route::has('user-management-new.roles.index')
            && \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled(
                'user_management_new_roles_view'
            )
            && $__umnManagedModuleView
            && $__umnManagedRolesPage
            && ($__umnPrivileged || $__umnAllows('roles.view'));

        $__umnCanUsers = $__umnUser
            && \Illuminate\Support\Facades\Route::has('user-management-new.users.index')
            && \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled(
                'user_management_new_users_view'
            )
            && $__umnManagedModuleView
            && $__umnManagedUsersPage
            && ($__umnPrivileged || $__umnAllows('user.view'));

        // Parent visibility follows Manage Side Bar / managed module View.
        // Child links remain controlled independently above.
        $__umnSidebarVisible = $__umnUser
            && $__umnManagedModuleView
            && \App\Utils\SidebarPermissionUtil::isManageSidebarEnabled('user_management_new');


    } catch (\Throwable $__umnSidebarException) {
        // Sidebar rendering must never make an otherwise valid page fail.
        $__umnSidebarVisible = false;
    }
@endphp

@if($__umnSidebarVisible)
    <li class="nav-item {{ $__umnSidebarActive ? 'active active-sub' : '' }}"
        data-sidebar-module="user_management_new">
        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#user-management-new-sidebar-menu"
           aria-expanded="{{ $__umnSidebarActive ? 'true' : 'false' }}"
           aria-controls="user-management-new-sidebar-menu">
            <i class="fa fa-users"></i>
            <span>User Management New</span>
        </a>
        <div id="user-management-new-sidebar-menu"
             class="collapse {{ $__umnSidebarActive ? 'show' : '' }}"
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">User Management:</h6>
                @if ($__umnCanRoles)
                    <a class="collapse-item {{ request()->routeIs('user-management-new.roles.*') ? 'active' : '' }}"
                       data-sidebar-page="user_management_new_roles"
                       href="{{ route('user-management-new.roles.index') }}">
                        <i class="fa fa-shield mr-1"></i>
                        Roles &amp; Permissions
                    </a>
                @endif
                {{--
                    MA-002 (LA-1135): Users.
                    Gated the same two ways the module gates Roles - the spatie
                    ability AND the automatic sidebar permission - so an admin
                    who has not enabled Users in the manage page does not see a
                    link that 403s when clicked.
                --}}
                @if ($__umnCanUsers)
                    <a class="collapse-item {{ request()->routeIs('user-management-new.users.*') ? 'active' : '' }}"
                       data-sidebar-page="user_management_new_users"
                       href="{{ route('user-management-new.users.index') }}">
                        <i class="fa fa-user mr-1"></i>
                        Users
                    </a>
                @endif
            </div>
        </div>
    </li>
@endif
