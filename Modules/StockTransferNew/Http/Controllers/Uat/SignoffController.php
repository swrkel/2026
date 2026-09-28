<?php

namespace Modules\StockTransferNew\Http\Controllers\Uat;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\Uat\SignoffRegisterService;

class SignoffController extends Controller
{
    public function index(SignoffRegisterService $service)
    {
        $records = $service->latest();
        return view('stocktransfernew::uat.signoff', compact('records'));
    }

    public function store(Request $request, SignoffRegisterService $service)
    {
        $service->store($request->only(['area', 'signed_by', 'status', 'remarks']));
        return redirect()->back()->with('status', __('stocktransfernew::uat.signoff_saved'));
    }
}
