<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Routing\Controller;

class PurchaseBillController extends Controller
{
    public function index()
    {
        return view('purchase::bills.index');
    }

    public function create()
    {
        return view('purchase::bills.create');
    }
}
