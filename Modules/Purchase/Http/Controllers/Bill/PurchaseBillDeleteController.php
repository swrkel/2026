<?php

namespace Modules\Purchase\Http\Controllers\Bill;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Bill\PurchaseBillDeleteService;

class PurchaseBillDeleteController extends Controller
{
    public function destroy($id, PurchaseBillDeleteService $service)
    {
        $service->delete($id);

        return response()->json(['success' => true, 'msg' => __('purchase::lang.deleted')]);
    }
}
