<?php

namespace Modules\BankingPaymentsHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingPaymentsHub\Services\PaymentsRegistryService;

class PaymentBatchController extends Controller
{
    public function index(PaymentsRegistryService $service)
    {
        return view('bankingpaymentshub::batches.index', [
            'title' => 'Batch & Bulk Payments',
            'records' => $service->items(),
        ]);
    }
}
