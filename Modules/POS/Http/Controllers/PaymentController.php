<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSMultiplePaymentService;

class PaymentController extends Controller
{
    public function store(Request $request, POSMultiplePaymentService $service)
    {
        $data = $service->validatePayments((float)$request->input('bill_total'), $request->input('payments', []));
        return response()->json(['success' => true, 'data' => $data]);
    }
}
