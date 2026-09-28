<?php

namespace Modules\Purchase\Http\Controllers\Return;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Return\PurchaseReturnEditService;

class PurchaseReturnEditController extends Controller
{
    public function edit($id, PurchaseReturnEditService $service)
    {
        return view('purchase::returns.edit', $service->formData($id));
    }

    public function update(\Illuminate\Http\Request $request, $id, PurchaseReturnEditService $service)
    {
        $service->update($id, $request->all());
        return redirect()->route('purchase.returns.index')->with('status', ['success' => true, 'msg' => __('purchase::lang.purchase_return_updated')]);
    }

}
