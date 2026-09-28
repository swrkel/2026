<?php

namespace Modules\BankingPaymentsHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingPaymentsHub\Services\PaymentsDashboardService;

class PaymentsDashboardController extends Controller
{
    public function index(PaymentsDashboardService $service)
    {
        return view('bankingpaymentshub::dashboard.index', [
            'title' => 'Payments Hub Dashboard',
            'summary' => $service->summary(),
        ]);
    }
}
