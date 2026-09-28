<?php

namespace Modules\Purchase\Http\Controllers\Bill;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Bill\PurchaseBillListService;

class PurchaseBillListController extends Controller
{
    public function index(PurchaseBillListService $service)
    {
        return view('purchase::bills.index', ['rows' => $service->paginate()]);
    }
}
