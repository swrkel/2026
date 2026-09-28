<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Settlements;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\SupplierPayment;
use Modules\AirlineTicketingNew\Entities\SupplierSettlement;
use Modules\AirlineTicketingNew\Services\Settlements\SupplierPaymentService;

class SupplierPaymentController extends Controller
{
    public function index()
    {
        $records = SupplierPayment::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::settlements.payments.index', compact('records'));
    }

    public function store(Request $request, SupplierSettlement $settlement, SupplierPaymentService $service)
    {
        abort_unless((int)$settlement->business_id === (int)session('business.id'), 404);

        $data = $request->validate([
            'payment_date' => ['required','date'],
            'payment_method' => ['required','string','max:30'],
            'payment_account' => ['nullable','string','max:150'],
            'reference_no' => ['nullable','string','max:100'],
            'exchange_rate' => ['required','numeric','gt:0'],
            'amount' => ['required','numeric','gt:0','max:' . $settlement->due_amount],
            'remarks' => ['nullable','string'],
        ]);

        $service->pay($settlement, $data);

        return back()->with('status', ['success' => 1, 'msg' => 'Supplier payment posted successfully.']);
    }
}
