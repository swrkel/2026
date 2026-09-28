<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerPaymentActionService;
use Modules\Customers\Services\CustomerSecurityDepositService;

class CustomerDepositController extends CustomerActionBaseController
{
    public function securityDeposit(
        $id,
        CustomerSecurityDepositService $service,
        CustomerPaymentActionService $paymentActionService
    ) {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $businessId = (int) request()->session()->get('user.business_id');
        $deposits = $service->rows($businessId, (int) $customer->id);
        $depositTotal = (float) $deposits->sum('amount');

        /*
         * IS2307: the Security Deposit form is saved through
         * CustomerPaymentActionService::acknowledge(), which validates the
         * payment method and its linked payment account.  The old form did not
         * receive those options, so every submit was rejected before a deposit
         * could be saved.  Use the same form-data source as the other Customer
         * payment actions so enabled methods/accounts stay in sync with the
         * business configuration.
         */
        $formData = $paymentActionService->formData($customer, 'security_deposit');

        return view('customers::deposits.security', array_merge(
            compact('customer', 'deposits', 'depositTotal'),
            $formData
        ));
    }

    public function storeSecurityDeposit(Request $request, $id, CustomerPaymentActionService $service)
    {
        $this->permissionService->authorize('edit');
        $customer = $this->getCustomer($id);
        $output = $service->acknowledge($request, $customer, 'security_deposit');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->route('customers.index')->with('status', $output);
    }
}
