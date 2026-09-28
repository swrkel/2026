<?php

namespace Modules\Purchase\Http\Controllers\Order;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Order\PurchaseOrderShowService;

class PurchaseOrderShowController extends Controller
{
    public function show($id, PurchaseOrderShowService $service)
    {
        return view('purchase::orders.show', ['order' => $service->find($id)]);
    }
}
