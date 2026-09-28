<?php

namespace Modules\BankingPaymentsHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingPaymentsHub\Services\PaymentsRegistryService;

class PaymentsSettingController extends Controller
{
    public function index(PaymentsRegistryService $service)
    {
        return view('bankingpaymentshub::settings.index', [
            'title' => 'Payments Settings',
            'settings' => $service->settings(),
        ]);
    }
}
