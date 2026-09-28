<?php

namespace Modules\RiceMill\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\RiceMill\Services\TenantContext;

/**
 * Defense-in-depth context guard for every Rice Mill web request.
 *
 * The host's tenant.context middleware remains authoritative and must run first.
 * This guard only verifies that tenancy is already initialized and that the
 * selected Business UID exists inside that active tenant database before any
 * Rice Mill controller, form action or report query is executed.
 */
class EnsureRiceMillContext
{
    public function __construct(private TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $tenantId = $this->context->tenantId();
        if ($tenantId === null || $tenantId === '') {
            abort(403, 'Rice Mill tenant context is not initialized.');
        }

        $businessId = $this->context->businessId();

        // Because tenant.context has already switched the default connection,
        // this existence check is executed inside the active tenant database.
        if (Schema::hasTable('business')) {
            $exists = DB::table('business')->where('id', $businessId)->exists();
            abort_unless(
                $exists,
                403,
                'The selected business does not belong to the active tenant context.'
            );
        }

        $request->attributes->set('rice_mill.tenant_id', $tenantId);
        $request->attributes->set('rice_mill.business_id', $businessId);

        return $next($request);
    }
}
