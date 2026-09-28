<?php

namespace App\Http\Middleware;

use App\Utils\SidebarPermissionUtil;
use Closure;
use Illuminate\Support\Facades\Log;

class EnforceBusinessSidebarModuleAccess
{
    /**
     * Blocks direct URL access when Super Admin > All Business > Manage Side Bar
     * has disabled the parent sidebar module for the current business.
     */
    public function handle($request, Closure $next)
    {
        // Central Super Admin keeps unrestricted administration access. After
        // Login As Business, however, the selected business's Manage policy is
        // authoritative for both its sidebar and direct URLs.
        if (SidebarPermissionUtil::hasSuperAdminBypass() && ! SidebarPermissionUtil::isSuperAdminInsideBusiness()) {
            return $next($request);
        }

        if (! auth()->check() || ! session()->has('user.business_id')) {
            return $next($request);
        }

        /*
         * Finance was separated from the legacy/core Accounting module and its
         * live List Accounts page now belongs to /finance/account.  Older
         * sidebar code/bookmarks can still point to /accounting-module/account.
         * When Finance is enabled this URL is now compatibility-only.  Always send
         * it to the authoritative Finance route before legacy Accounting gates
         * or controller subscription checks can run.
         *
         * The legacy List Accounts root is retired for Finance.  Redirect this
         * safe GET/HEAD entry unconditionally.  Access policy is enforced after
         * the redirect by the Finance route itself.  Otherwise a stale
         * Finance sidebar link can still enter legacy AccountController and its
         * historical subscription check redirects the user to Home.
         *
         * /finance/account then passes through this middleware again and still
         * has to satisfy Manage Side Bar, Manage Page New and role gates.  No
         * write/update endpoint is bypassed.
         */
        $requestPath = trim((string) $request->path(), '/');
        if (in_array(strtoupper((string) $request->method()), ['GET', 'HEAD'], true)
            && $requestPath === 'accounting-module/account') {
            $target = url('/finance/account');
            $query = (string) $request->getQueryString();
            if ($query !== '') {
                $target .= '?' . $query;
            }

            return redirect()->to($target);
        }

        /*
         * User Management New owns the Roles page when enabled.
         * Keep the legacy /roles URL as a compatibility entry so old sidebar
         * links/bookmarks cannot be blocked by the retired user_management gate.
         */
        if (in_array(strtoupper((string) $request->method()), ['GET', 'HEAD'], true)
            && $requestPath === 'roles'
            && SidebarPermissionUtil::isManageSidebarEnabled('user_management_new')
            && \Illuminate\Support\Facades\Route::has('user-management-new.roles.index')) {
            return redirect()->route('user-management-new.roles.index');
        }

        $moduleKeys = SidebarPermissionUtil::routeModuleKeysForRequest($request);
        if (empty($moduleKeys)) {
            return $next($request);
        }

        foreach ($moduleKeys as $moduleKey) {
            // This first gate represents only the parent Manage Side Bar
            // switch. Page/role permissions are enforced separately below.
            // isVisibleInSidebar() combines both layers and can therefore
            // produce a false "disabled from Manage Side Bar" response when
            // the parent module is enabled but a child permission is not.
            if (! SidebarPermissionUtil::isManageSidebarEnabled($moduleKey)) {
                Log::warning('Blocked direct URL access to a Manage Side Bar-disabled module.', [
                    'business_id' => (int) session('user.business_id'),
                    'user_id' => (int) auth()->id(),
                    'module_key' => (string) $moduleKey,
                    'method' => (string) $request->method(),
                    'path' => (string) $request->path(),
                    'route_name' => optional($request->route())->getName(),
                ]);

                /*
                 * MA-002: name the switch that blocked the request.
                 *
                 * "This module has been disabled from Manage Side Bar for this
                 * business" told the user nothing they could act on. The Petro
                 * General tank save is the example: the module IS enabled and
                 * visible in the sidebar, the list page opens, and only the
                 * save is refused - because this loop checks EVERY key the
                 * request resolves to, not just the module. One unchecked
                 * sub-item is enough, and the old message never said which.
                 *
                 * The key is already written to the log. Putting it in the
                 * message too means whoever hits it can go straight to that
                 * switch in Manage Side Bar instead of raising a ticket.
                 */
                $blockedMessage = 'This module has been disabled from Manage Side Bar for this business.'
                    . ' (Blocked by the setting: ' . $moduleKey . ')';

                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'msg' => $blockedMessage,
                        'blocked_key' => (string) $moduleKey,
                    ], 403);
                }

                abort(403, $blockedMessage);
            }
        }

        foreach (SidebarPermissionUtil::permissionKeysForRequest($request) as $permissionKey) {
            if (SidebarPermissionUtil::isAutomaticPermissionEnabled($permissionKey)) {
                continue;
            }

            Log::warning('Blocked direct URL access to a Manage New-disabled module page.', [
                'business_id' => (int) session('user.business_id'),
                'user_id' => (int) auth()->id(),
                'permission_key' => (string) $permissionKey,
                'method' => (string) $request->method(),
                'path' => (string) $request->path(),
                'route_name' => optional($request->route())->getName(),
            ]);

            // MA-002: same reasoning as the module gate above - name the
            // setting so the person hitting it can act on it.
            $blockedPageMessage = 'This page has been disabled from Super Admin Manage New for this business.'
                . ' (Blocked by the setting: ' . $permissionKey . ')';

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => $blockedPageMessage,
                    'blocked_key' => (string) $permissionKey,
                ], 403);
            }

            abort(403, $blockedPageMessage);
        }

        return $next($request);
    }
}
