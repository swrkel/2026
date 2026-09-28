<?php

namespace Modules\BankingPaymentsHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingPaymentsHub\Services\PaymentsRegistryService;

class PaymentQueueController extends Controller
{
    public function index(PaymentsRegistryService $service)
    {
        return view('bankingpaymentshub::queue.index', [
            'title' => 'Payment Queue',
            'records' => $service->items(),
        ]);
    }
}
