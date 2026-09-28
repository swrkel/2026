<?php

namespace Modules\Purchase\Http\Controllers\Bill;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Bill\PurchaseBillCreateService;

class PurchaseBillCreateController extends Controller
{
    public function create(PurchaseBillCreateService $service)
    {
        return view('purchase::bills.create', $service->formData());
    }

    public function store(Request $request, PurchaseBillCreateService $service)
    {
        $service->store($request->all());

        return redirect()->route('purchase.bills.index')->with('status', [
            'success' => true,
            'msg' => __('purchase::lang.purchase_bill_saved')
        ]);
    }
}
