<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\AdvancedPaymentService;

class AdvancedPaymentController extends Controller
{
    public function __construct(private AdvancedPaymentService $paymentService) {}

    public function validatePayments(Request $request)
    {
        return response()->json($this->paymentService->validateMultiplePayments($request->all()));
    }

    public function reverse(Request $request)
    {
        return response()->json($this->paymentService->reversePayment($request->all()));
    }
}
