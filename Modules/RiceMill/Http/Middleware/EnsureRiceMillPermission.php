<?php
namespace Modules\RiceMill\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\RiceMill\Services\PermissionAccessService;

class EnsureRiceMillPermission
{
    public function __construct(private PermissionAccessService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($this->permissions->allows($user, $permission)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this Rice Mill page.');
    }
}
