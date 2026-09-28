<?php
namespace Modules\Audit\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditPermission
{
    public function handle($request, Closure $next, $permission = 'audit.view')
    {
        $user = auth()->user();
        if (!$user) abort(403);

        // If the host app has not imported Audit permissions yet, do not break the ERP.
        // Once the permission exists, enforce it through the host authorization layer.
        try {
            if (Schema::hasTable('permissions') && DB::table('permissions')->where('name', $permission)->exists()) {
                if (method_exists($user, 'can') && !$user->can($permission)) abort(403, 'You do not have permission to access this Audit page.');
            }
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Permission infrastructure differences must not cause a 500 error.
        }
        return $next($request);
    }
}
