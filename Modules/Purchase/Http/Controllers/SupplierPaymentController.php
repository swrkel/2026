<?php

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Routing\Controller;

class SupplierPaymentController extends Controller
{
    public function index()
    {
        return view('purchase::payments.index');
    }
}
