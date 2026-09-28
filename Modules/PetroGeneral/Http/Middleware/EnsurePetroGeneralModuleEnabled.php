<?php

namespace Modules\PetroGeneral\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Support\PetroGeneralAccess;

class EnsurePetroGeneralModuleEnabled
{
    /**
     * Block every Petro General URL when the Petro General module is not enabled for the tenant.
     * This is intentionally enforced at route level so typing a direct URL also fails.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = $request->session()->get('user.business_id')
            ?? $request->session()->get('business.id')
            ?? optional(auth()->user())->business_id;

        if (empty($businessId)) {
            abort(403, 'Unauthorized action.');
        }

        if (!\App\Utils\SidebarPermissionUtil::isEnabled('petro_general', (int) $businessId)) {
            abort(403, 'Petro General module is not enabled for this business.');
        }

        if (!PetroGeneralAccess::userCanAccess(auth()->user(), (int) $businessId)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
