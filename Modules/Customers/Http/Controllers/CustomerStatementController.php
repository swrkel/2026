<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Services\CustomerLedgerService;

class CustomerStatementController extends CustomerActionBaseController
{
    /**
     * Standalone Customers Module statement popup.
     *
     * This intentionally uses Customers module route/controller/view files and
     * does not route through legacy shared statement controllers or old contact statement views.
     */
    public function show(CustomerLedgerService $ledgerService, $id)
    {
        $this->permissionService->authorize('view');

        $customer = $this->getCustomer($id);
        $businessId = (int) request()->session()->get('user.business_id');
        $rows = $ledgerService->ledgerRows($businessId, (int) $customer->id, 500, false, $customer);

        return view('customers::statements.show', compact('customer', 'rows'));
    }
}
