<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VoucherSaleController extends Controller
{
    public function index()
    {
        return view('beautysaloons::'.strtolower(str_replace('Controller','', 'VoucherSaleController')).'.index');
    }

    public function create()
    {
        return view('beautysaloons::'.strtolower(str_replace('Controller','', 'VoucherSaleController')).'.create');
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('beautysaloons::gift_vouchers.saved_successfully'));
    }
}
