<?php

namespace Modules\Purchase\Http\Controllers\Payment;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Payment\SupplierPaymentDeleteService;

class SupplierPaymentDeleteController extends Controller
{
    public function destroy($id, SupplierPaymentDeleteService $service)
    {
        $service->delete($id);

        return response()->json(['success' => true, 'msg' => __('purchase::lang.deleted')]);
    }
}
