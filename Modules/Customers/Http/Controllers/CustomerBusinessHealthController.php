<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerHealthScoreService;

class CustomerBusinessHealthController extends CustomerPortalController
{
    public function index(Request $request, CustomerHealthScoreService $healthService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        $health = $healthService->score((int) $businessId, (int) $customer->id, $customer);

        return view('customers::portal.business_health', compact('customer', 'health'));
    }
}
