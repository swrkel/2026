<?php

namespace Modules\Purchase\Http\Controllers\Bill;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Bill\PurchaseBillEditService;

class PurchaseBillEditController extends Controller
{
    public function edit($id, PurchaseBillEditService $service)
    {
        return view('purchase::bills.edit', $service->formData($id));
    }

    public function update(Request $request, $id, PurchaseBillEditService $service)
    {
        $service->update($id, $request->all());

        return redirect()->route('purchase.bills.index')->with('status', [
            'success' => true,
            'msg' => __('purchase::lang.purchase_bill_updated')
        ]);
    }
}
