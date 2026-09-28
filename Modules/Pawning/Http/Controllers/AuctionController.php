<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Pawning\Models\Pledge;

class AuctionController extends Controller
{
    public function index()
    {
        $pledges = Pledge::where('status', 'active')->whereDate('due_on', '<', date('Y-m-d'))->orderBy('due_on')->paginate(20);
        return view('pawning::auction.index', compact('pledges'));
    }

    public function mark($id)
    {
        $pledge = Pledge::findOrFail($id);
        $pledge->status = 'auctioned';
        $pledge->workflow_status = 'auctioned';
        $pledge->save();
        return redirect()->route('pawning.auction.index')->with('status', ['success' => 1, 'msg' => 'Pledge marked as auctioned']);
    }
}
