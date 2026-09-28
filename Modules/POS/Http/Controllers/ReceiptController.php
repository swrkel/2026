<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;

class ReceiptController extends Controller
{
    public function index()
    {
        return view('pos::receipts.index');
    }
}
