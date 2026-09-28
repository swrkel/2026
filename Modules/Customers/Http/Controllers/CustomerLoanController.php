<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerPaymentActionService;

class CustomerLoanController extends CustomerActionBaseController
{
    public function create(CustomerPaymentActionService $service, $id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $mode = 'loan_to_customer';
        $formData = $service->formData($customer, $mode);

        return view('customers::loans.form', array_merge(compact('customer', 'mode'), $formData));
    }

    public function store(Request $request, $id, CustomerPaymentActionService $service)
    {
        $this->permissionService->authorize('edit');
        $customer = $this->getCustomer($id);
        $output = $service->acknowledge($request, $customer, 'loan_to_customer');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->route('customers.index')->with('status', $output);
    }
}
