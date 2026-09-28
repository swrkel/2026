<?php

namespace Modules\Purchase\Http\Controllers\Order;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Order\PurchaseOrderEditService;

class PurchaseOrderEditController extends Controller
{
    public function edit($id, PurchaseOrderEditService $service)
    {
        return view('purchase::orders.edit', $service->formData($id));
    }

    public function update(Request $request, $id, PurchaseOrderEditService $service)
    {
        $service->update($id, $request->all());

        return redirect()->route('purchase.orders.index')->with('status', [
            'success' => true,
            'msg' => __('purchase::lang.purchase_order_updated')
        ]);
    }
}
