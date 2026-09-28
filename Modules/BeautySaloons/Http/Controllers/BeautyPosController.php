<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\BeautyBillingService;

class BeautyPosController extends Controller
{
    public function index()
    {
        return view('beautysaloons::pos.index');
    }

    public function calculate(Request $request, BeautyBillingService $service)
    {
        return response()->json($service->calculate($request->input('lines', []), (float)$request->input('discount_amount', 0)));
    }

    public function checkout(Request $request, BeautyBillingService $service)
    {
        $bill = $service->checkout($request->all());
        return response()->json(['success' => true, 'msg' => __('beautysaloons::bs014.bill_saved'), 'bill_id' => $bill->id]);
    }
}
