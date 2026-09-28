<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;

class LoyaltyController extends Controller
{
    public function index()
    {
        return view('pos::loyalty.index');
    }
}
