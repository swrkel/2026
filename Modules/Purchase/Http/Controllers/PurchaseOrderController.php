<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\PurchaseOrder\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    public function index(PurchaseOrderService $service)
    {
        return view('purchase::orders.index', ['rows' => $service->list()]);
    }

    public function create()
    {
        return view('purchase::orders.create');
    }

    public function store(Request $request, PurchaseOrderService $service)
    {
        $service->store($request->all());

        return redirect()->route('purchase.orders.index')->with('status', [
            'success' => true,
            'msg' => __('purchase::lang.purchase_order_saved')
        ]);
    }

    public function edit($id, PurchaseOrderService $service)
    {
        return view('purchase::orders.edit', ['row' => $service->find($id)]);
    }

    public function update(Request $request, $id, PurchaseOrderService $service)
    {
        $service->update($id, $request->all());

        return redirect()->route('purchase.orders.index')->with('status', [
            'success' => true,
            'msg' => __('purchase::lang.purchase_order_updated')
        ]);
    }

    public function destroy($id, PurchaseOrderService $service)
    {
        $service->delete($id);

        return response()->json(['success' => true, 'msg' => __('purchase::lang.deleted')]);
    }
}
