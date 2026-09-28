<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Leasing\Models\LeaseContract;

class InsuranceController extends Controller
{
    public function index()
    {
        $lease_contracts = LeaseContract::where('status', 'active')->whereDate('due_on', '<', date('Y-m-d'))->orderBy('due_on')->paginate(20);
        return view('leasing::insurance.index', compact('lease_contracts'));
    }

    public function mark($id)
    {
        $lease_contract = LeaseContract::findOrFail($id);
        $lease_contract->status = 'insuranceed';
        $lease_contract->workflow_status = 'insuranceed';
        $lease_contract->save();
        return redirect()->route('leasing.insurance.index')->with('status', ['success' => 1, 'msg' => 'LeaseContract marked as insuranceed']);
    }
}
