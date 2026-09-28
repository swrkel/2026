<?php

namespace Modules\BankingPaymentsHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingPaymentsHub\Services\PaymentsRegistryService;

class PaymentReconciliationController extends Controller
{
    public function index(PaymentsRegistryService $service)
    {
        return view('bankingpaymentshub::reconciliation.index', [
            'title' => 'Payment Reconciliation',
            'records' => $service->items(),
        ]);
    }
}
