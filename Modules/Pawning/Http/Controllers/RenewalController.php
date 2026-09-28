<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pawning\Models\Pledge;

class RenewalController extends Controller
{
    public function index()
    {
        $pledges = Pledge::where('status', 'active')->orderBy('due_on')->paginate(20);
        return view('pawning::renewals.index', compact('pledges'));
    }

    public function renew(Request $request, $id)
    {
        $pledge = Pledge::findOrFail($id);
        $pledge->due_on = $request->get('due_on') ?: date('Y-m-d', strtotime('+30 days'));
        $pledge->workflow_status = 'active';
        $pledge->status = 'active';
        $pledge->save();
        return redirect()->route('pawning.renewals.index')->with('status', ['success' => 1, 'msg' => 'Pledge renewed successfully']);
    }
}
