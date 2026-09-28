<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerPaymentActionService;

class CustomerRefundController extends CustomerActionBaseController
{
    public function refundPayment($id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $mode = 'refund_payment';

        return view('customers::refunds.form', compact('customer', 'mode'));
    }

    public function store(Request $request, $id, CustomerPaymentActionService $service)
    {
        $this->permissionService->authorize('edit');
        $customer = $this->getCustomer($id);
        $output = $service->acknowledge($request, $customer, 'refund_payment');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->route('customers.index')->with('status', $output);
    }
}
