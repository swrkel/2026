<?php

namespace Modules\BankingPaymentsHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingPaymentsHub\Services\PaymentsRegistryService;

class PaymentRouteController extends Controller
{
    public function index(PaymentsRegistryService $service)
    {
        return view('bankingpaymentshub::routing.index', [
            'title' => 'Payment Routing',
            'records' => $service->items(),
        ]);
    }
}
