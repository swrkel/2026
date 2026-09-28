<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Leasing\Models\LeaseContract;

class RestructureController extends Controller
{
    public function index()
    {
        $lease_contracts = LeaseContract::where('status', 'active')->orderBy('due_on')->paginate(20);
        return view('leasing::restructures.index', compact('lease_contracts'));
    }

    public function renew(Request $request, $id)
    {
        $lease_contract = LeaseContract::findOrFail($id);
        $lease_contract->due_on = $request->get('due_on') ?: date('Y-m-d', strtotime('+30 days'));
        $lease_contract->workflow_status = 'active';
        $lease_contract->status = 'active';
        $lease_contract->save();
        return redirect()->route('leasing.restructures.index')->with('status', ['success' => 1, 'msg' => 'LeaseContract renewed successfully']);
    }
}
