<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerPaymentActionService;

class CustomerPaymentController extends CustomerActionBaseController
{
    public function payDue(CustomerPaymentActionService $service, $id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $mode = 'pay_due';
        $formData = $service->formData($customer, $mode);

        return view('customers::payments.form', array_merge(compact('customer', 'mode'), $formData));
    }

    public function accountsByMethod(Request $request, CustomerPaymentActionService $service)
    {
        $this->permissionService->authorize('view');

        $businessId = (int) $request->session()->get('user.business_id');
        $method = (string) $request->input('method', '');
        $accounts = $service->paymentAccountOptions($businessId, $method);
        return response()->json([
            'success' => 1,
            'accounts' => $accounts,
            // S410: no default account. User must choose Please Select -> account.
            'default_account_id' => null,
        ]);
    }

    public function store(Request $request, $id, CustomerPaymentActionService $service)
    {
        $this->permissionService->authorize('edit');
        $customer = $this->getCustomer($id);
        $output = $service->acknowledge($request, $customer, 'pay_due');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->route('customers.register')->with('status', $output);
    }
}
