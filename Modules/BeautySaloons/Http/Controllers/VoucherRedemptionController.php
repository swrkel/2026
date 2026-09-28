<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VoucherRedemptionController extends Controller
{
    public function index()
    {
        return view('beautysaloons::'.strtolower(str_replace('Controller','', 'VoucherRedemptionController')).'.index');
    }

    public function create()
    {
        return view('beautysaloons::'.strtolower(str_replace('Controller','', 'VoucherRedemptionController')).'.create');
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('beautysaloons::gift_vouchers.saved_successfully'));
    }
}
