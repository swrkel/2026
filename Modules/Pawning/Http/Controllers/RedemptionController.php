<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pawning\Models\Pledge;
use Modules\Pawning\Services\PledgeService;

class RedemptionController extends Controller
{
    public function index()
    {
        $pledges = Pledge::where('status', 'active')->orderBy('due_on')->paginate(20);
        return view('pawning::redemptions.index', compact('pledges'));
    }

    public function redeem(Request $request, $id, PledgeService $service)
    {
        $pledge = Pledge::findOrFail($id);
        $service->redeem($pledge, $request->get('amount', $pledge->outstanding_amount), $request->get('transaction_date'));
        return redirect()->route('pawning.redemptions.index')->with('status', ['success' => 1, 'msg' => 'Pledge redeemed successfully']);
    }
}
