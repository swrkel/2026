<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Leasing\Models\LeaseContract;
use Modules\Leasing\Services\LeaseContractService;

class PaymentController extends Controller
{
    public function index()
    {
        $lease_contracts = LeaseContract::where('status', 'active')->orderBy('due_on')->paginate(20);
        return view('leasing::payments.index', compact('lease_contracts'));
    }

    public function redeem(Request $request, $id, LeaseContractService $service)
    {
        $lease_contract = LeaseContract::findOrFail($id);
        $service->redeem($lease_contract, $request->get('amount', $lease_contract->outstanding_amount), $request->get('transaction_date'));
        return redirect()->route('leasing.payments.index')->with('status', ['success' => 1, 'msg' => 'LeaseContract redeemed successfully']);
    }
}
