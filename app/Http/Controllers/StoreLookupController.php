<?php

namespace App\Http\Controllers;

use App\Store;
use App\Utils\Util;
use Illuminate\Http\Request;

/**
 * S678-RETIRE: core home for the Business Location -> Store dropdown.
 *
 * WHY THIS EXISTS
 *   The store dropdown handlers in public/js/app.js are GLOBAL - they fire on
 *   any page carrying a #location_id or #store_id field. That includes plain
 *   core screens such as Suppliers -> Advance Payment, which have nothing to
 *   do with fuel at all.
 *
 *   Those handlers used to call /petro/get-stores-by-id. On a business with
 *   the old Petro module switched off, EnforceBusinessSidebarModuleAccess
 *   refuses that request and the user is told the module is disabled while
 *   doing something entirely unrelated. That is why petro_module = 1 has been
 *   left switched on at ep127 against the customer's wishes (MA 007 section 5).
 *
 *   The lookup itself is not a Petro feature. It reads App\Store, scoped to
 *   the business - the same query every module's copy was running. Giving it a
 *   core route removes the last reason for a non-fuel page to depend on Petro.
 *
 * The module-prefixed copies (/petropd, /petro-general, /petro) are left in
 * place. Pages inside those modules keep using their own; only the fallback
 * changes.
 */
class StoreLookupController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Stores for a business location, as <option> html for the dropdown.
     */
    public function getStoresById(Request $request)
    {
        $business_id = $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id;

        if (empty($business_id)) {
            return $this->commonUtil->createDropdownHtml(collect([]), 'Please Select');
        }

        $stores = Store::where('business_id', $business_id);

        if ($request->filled('location_id')) {
            $stores = $stores->where('location_id', $request->location_id);
        }

        $stores = $stores->pluck('name', 'id');

        return $this->commonUtil->createDropdownHtml($stores, 'Please Select');
    }
}
