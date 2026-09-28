<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\BeautyCashierSettlementService;

class BeautyCashierSettlementController extends Controller
{
    public function index()
    {
        return view('beautysaloons::pos.cashier_settlements');
    }

    public function finalize(Request $request, BeautyCashierSettlementService $service)
    {
        $settlement = $service->finalize($request->all());
        return response()->json(['success' => true, 'settlement_id' => $settlement->id]);
    }
}
