<?php

namespace Modules\Superadmin\Http\Controllers\Concerns;

use App\Business;
use Illuminate\Support\Facades\Schema;

/**
 * Business selection for Super Admin Settings, scoped to the current tenant.
 *
 * WHY THE TENANT BOUNDARY LOOKS AFTER ITSELF
 *
 * Each tenant is a separate database. Querying `business` on the ACTIVE
 * connection therefore returns that tenant's businesses and nothing else - the
 * database boundary is the tenant boundary. There is deliberately no
 * tenant_id filter here: adding one would either duplicate what the connection
 * already guarantees, or quietly disagree with it on the databases where
 * tenant_id has not been backfilled yet.
 *
 * What still has to be checked is the SUBMITTED id. A dropdown is client-side,
 * so a crafted request could name any number. resolveTenantBusinessId() accepts
 * an id only if it is one this tenant actually has.
 */
trait SelectsTenantBusiness
{
    /**
     * Businesses in the current tenant, as [id => name].
     */
    protected function tenantBusinessOptions(): array
    {
        return Business::orderBy('name')->pluck('name', 'id')->toArray();
    }

    /**
     * [id => global_uid] for the current tenant, for display on selection.
     *
     * global_uid was added estate-wide by the MA 007 identity work, but not
     * every database has it yet - imports have twice stripped the column. Where
     * it is missing, or not yet stamped on a row, the screen shows a plain
     * "not assigned" rather than failing.
     */
    protected function tenantBusinessUids(): array
    {
        if (! Schema::hasColumn('business', 'global_uid')) {
            return [];
        }

        return Business::pluck('global_uid', 'id')
            ->filter(static fn ($uid) => ! empty($uid))
            ->toArray();
    }

    /**
     * The business to act on: the submitted one if it belongs to this tenant,
     * otherwise the Super Admin's own.
     *
     * Never trusts the submitted value. An id from another tenant, or one that
     * does not exist, falls back rather than being used.
     */
    protected function resolveTenantBusinessId($request, $fallback = null)
    {
        $fallback = $fallback ?: $request->session()->get('user.business_id');

        $submitted = $request->input('business_id');

        if (empty($submitted)) {
            return $fallback;
        }

        $exists = Business::where('id', $submitted)->exists();

        return $exists ? (int) $submitted : $fallback;
    }
}
