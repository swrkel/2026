<?php

namespace Modules\Purchase\Http\Controllers\Order;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Order\PurchaseOrderListService;

class PurchaseOrderListController extends Controller
{
    public function index(PurchaseOrderListService $service)
    {
        return view('purchase::orders.index', ['rows' => $service->paginate()]);
    }
}
