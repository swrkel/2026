<?php

namespace Modules\Purchase\Http\Controllers\Bill;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Bill\PurchaseBillShowService;

class PurchaseBillShowController extends Controller
{
    public function show($id, PurchaseBillShowService $service)
    {
        return view('purchase::bills.show', ['bill' => $service->find($id)]);
    }
}
