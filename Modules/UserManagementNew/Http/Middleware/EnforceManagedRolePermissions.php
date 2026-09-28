<?php

namespace Modules\UserManagementNew\Http\Middleware;

use App\Services\AutomaticModuleRegistry;
use App\Utils\SidebarPermissionUtil;
use Closure;
use Illuminate\Http\Request;
use Modules\UserManagementNew\Services\BusinessPermissionBridge;
use Modules\UserManagementNew\Services\RolePermissionService;

class EnforceManagedRolePermissions
{
    public function __construct(
        private BusinessPermissionBridge $bridge,
        private RolePermissionService $permissions
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $businessId = (int) ($request->session()->get('business.id')
            ?: $request->session()->get('user.business_id')
            ?: ($user->business_id ?? 0));

        /*
         * S715: heal legacy UserManagementNew roles before the sidebar or route
         * gates evaluate them. Older roles can have umn.module/page rights but
         * no umn.managed marker, and roles saved before the compatibility bridge
         * do not yet carry the Petro PD permissions that Petro PD itself checks.
         *
         * Do this once per login/session for performance. Newly saved roles are
         * already correct because RolePermissionService::sync() applies the same
         * bridge immediately.
         */
        if ($businessId > 0 && $request->hasSession()) {
            // S759: versioned key intentionally forces one fresh repair for
            // already logged-in users after adding the Suppliers compatibility
            // bridge. Reusing the old S730 marker would leave the current session
            // with the stale role until logout/login.
            $repairKey = 'umn_s759_suppliers_role_repair_' . (int) $user->id . '_' . $businessId;
            if ($request->session()->get($repairKey) !== 1) {
                $this->permissions->repairAssignedManagedRoles($user, $businessId);
                $request->session()->put($repairKey, 1);
            }
        }

        if (SidebarPermissionUtil::hasSuperAdminBypass()
            || ($businessId > 0 && $user->hasRole('Admin#' . $businessId))
            || !SidebarPermissionUtil::usesManagedRoleForCurrentUser($businessId)) {
            return $next($request);
        }

        $moduleKeys = array_values(array_unique(array_filter(array_map(
            [AutomaticModuleRegistry::class, 'normalizeKey'],
            $this->bridge->moduleKeysForRequest($request)
        ))));
        if ($moduleKeys === []) {
            return $next($request);
        }

        $right = $this->rightForRequest($request);
        $requestPageKeys = array_values(array_unique(array_filter(array_map(
            static fn ($key): string => AutomaticModuleRegistry::normalizeKey((string) $key),
            $this->bridge->permissionKeysForRequest($request)
        ))));

        /*
         * IS2347: a child page permission is sufficient to make its parent
         * visible/openable, even for roles saved by older builds before parent
         * View was derived automatically. New saves still receive parent View in
         * RoleController, so this is primarily a backward-compatibility safety net.
         */
        $requestHasAllowedPage = false;
        foreach ($requestPageKeys as $pageKey) {
            if (SidebarPermissionUtil::managedRoleAllowsPermission(
                $this->permissions->pagePermission($pageKey),
                $businessId
            )) {
                $requestHasAllowedPage = true;
                break;
            }
        }

        foreach ($moduleKeys as $moduleKey) {
            $moduleViewAllowed = SidebarPermissionUtil::managedRoleAllowsPermission(
                $this->permissions->modulePermission($moduleKey, 'view'),
                $businessId
            );

            if (!$moduleViewAllowed && !$requestHasAllowedPage) {
                return $this->deny($request);
            }

            if ($right !== 'view'
                && !SidebarPermissionUtil::managedRoleAllowsPermission(
                    $this->permissions->modulePermission($moduleKey, $right),
                    $businessId
                )) {
                return $this->deny($request);
            }
        }

        // A page/tab View permission is required for every HTTP method, not just
        // GET. This prevents a direct POST/PUT/DELETE from bypassing a page the
        // role is not allowed to open.
        foreach ($requestPageKeys as $pageKey) {
            if (!SidebarPermissionUtil::managedRoleAllowsPermission(
                $this->permissions->pagePermission((string) $pageKey),
                $businessId
            )) {
                return $this->deny($request);
            }
        }

        return $next($request);
    }

    private function rightForRequest(Request $request): string
    {
        $route = $request->route();
        $action = $route ? (array) $route->getAction() : [];
        $haystack = strtolower(implode(' ', [
            $request->path(),
            (string) optional($route)->getName(),
            (string) ($action['controller'] ?? ''),
        ]));

        foreach (['whatsapp', 'email', 'pdf', 'print'] as $right) {
            if (str_contains($haystack, $right)) {
                return $right;
            }
        }
        if ($request->isMethod('DELETE')
            || preg_match('/(?:destroy|delete|remove|purge)/', $haystack)) {
            return 'delete';
        }
        if (!$request->isMethod('GET')
            || preg_match('/(?:create|store|edit|update|add|save|approve|reject|assign|adjust)/', $haystack)) {
            return 'edit';
        }

        return 'view';
    }

    private function deny(Request $request)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'This role does not have permission for this operation.',
            ], 403);
        }

        abort(403, 'This role does not have permission for this operation.');
    }
}
