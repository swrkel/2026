<?php

namespace Modules\PetroDirect\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DailyVoucherController extends Controller
{
    public function create(Request $request)
    {
        return response('<div class="modal-body"><p>Daily voucher entry form is not available in this PetroDirect package.</p></div>');
    }

    public function store(Request $request)
    {
        return response()->json(['success' => false, 'msg' => 'Daily voucher save is not available in this PetroDirect package.']);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['success' => false, 'msg' => 'Daily voucher update is not available in this PetroDirect package.']);
    }
}
