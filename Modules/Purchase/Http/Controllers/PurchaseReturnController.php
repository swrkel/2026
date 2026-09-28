<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Routing\Controller;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        return view('purchase::returns.index');
    }
}
