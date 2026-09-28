<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyBill;
use Modules\BeautySaloons\Entities\BeautyBillPayment;

class BeautyBillingController extends Controller
{
    public function index()
    {
        return view('beautysaloons::billing.index');
    }

    public function receipt($id)
    {
        $bill = BeautyBill::findOrFail($id);
        return view('beautysaloons::billing.receipt', compact('bill'));
    }

    public function addPayment(Request $request, $id)
    {
        $bill = BeautyBill::findOrFail($id);
        BeautyBillPayment::create($request->only(['payment_method', 'reference_no', 'amount', 'note']) + ['bill_id' => $bill->id]);
        return response()->json(['success' => true, 'msg' => __('beautysaloons::bs014.payment_saved')]);
    }
}
