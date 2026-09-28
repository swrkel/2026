<?php

namespace Modules\Purchase\Http\Controllers\Payment;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Payment\SupplierPaymentCreateService;

class SupplierPaymentCreateController extends Controller
{
    public function create(SupplierPaymentCreateService $service)
    {
        return view('purchase::payments.create', $service->formData());
    }

    public function store(Request $request, SupplierPaymentCreateService $service)
    {
        $service->store($request->all());

        return redirect()->route('purchase.supplier-payments.index')->with('status', [
            'success' => true,
            'msg' => __('purchase::lang.supplier_payment_saved')
        ]);
    }
}
