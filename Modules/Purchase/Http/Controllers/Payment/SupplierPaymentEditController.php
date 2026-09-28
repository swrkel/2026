<?php

namespace Modules\Purchase\Http\Controllers\Payment;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Payment\SupplierPaymentEditService;

class SupplierPaymentEditController extends Controller
{
    public function edit($id, SupplierPaymentEditService $service)
    {
        return view('purchase::payments.edit', $service->formData($id));
    }

    public function update(Request $request, $id, SupplierPaymentEditService $service)
    {
        $service->update($id, $request->all());

        return redirect()->route('purchase.supplier-payments.index')->with('status', [
            'success' => true,
            'msg' => __('purchase::lang.supplier_payment_updated')
        ]);
    }
}
