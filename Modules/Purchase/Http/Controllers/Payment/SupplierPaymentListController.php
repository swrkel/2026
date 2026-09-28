<?php

namespace Modules\Purchase\Http\Controllers\Payment;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Payment\SupplierPaymentListService;

class SupplierPaymentListController extends Controller
{
    public function index(SupplierPaymentListService $service)
    {
        return view('purchase::payments.index', ['rows' => $service->paginate()]);
    }
}
