<?php
namespace Modules\EggManagement\Http\Middleware;

use Closure;
use Modules\EggManagement\Services\AuthorizationService;

class EggPermission
{
    protected $authz;
    public function __construct(AuthorizationService $authz) { $this->authz = $authz; }

    public function handle($request, Closure $next, $permission)
    {
        if (!$this->authz->allows($permission)) {
            abort(403, 'You do not have permission to access this Egg Management function.');
        }
        return $next($request);
    }
}
