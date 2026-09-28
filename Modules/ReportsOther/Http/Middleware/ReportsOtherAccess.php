<?php

namespace Modules\ReportsOther\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportsOtherAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->guest(route('login'));
        }

        $businessId = (int) ($request->session()->get('user.business_id') ?: ($request->user()->business_id ?? 0));

        /*
         * System-standard parent gate.
         * Super Admin -> All Businesses -> Manage Side Bar is authoritative.
         * This module does not maintain a second enable/disable source.
         */
        if ($businessId > 0 && class_exists('App\\Utils\\SidebarPermissionUtil')) {
            try {
                if (!\App\Utils\SidebarPermissionUtil::isEnabled('reports_other', $businessId)) {
                    abort(403, 'This module has been disabled from Manage Side Bar for this business.');
                }
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // If the host utility is unavailable during install/bootstrap,
                // continue to the normal role/page permission gate below.
            }
        }

        $user = $request->user();
        $allowed = false;

        if (method_exists($user, 'can')) {
            try {
                $allowed = $user->can('superadmin')
                    || $user->can('reports_other.view')
                    || $user->can('reports_other.cash_receipt.view');
            } catch (\Throwable $e) {
                $allowed = false;
            }
        }

        // During first deployment permissions may not have been synchronized yet.
        // Keep the installation usable only when the host permission package cannot
        // resolve any of this module's permission names at all.
        if (!$allowed && method_exists($user, 'hasPermissionTo')) {
            try {
                $allowed = $user->hasPermissionTo('reports_other.view')
                    || $user->hasPermissionTo('reports_other.cash_receipt.view');
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                $allowed = true;
            } catch (\Throwable $e) {
                $allowed = false;
            }
        }

        if (!$allowed && (method_exists($user, 'can') || method_exists($user, 'hasPermissionTo'))) {
            abort(403);
        }

        return $next($request);
    }
}
