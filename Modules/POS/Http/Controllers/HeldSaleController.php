<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSCartService;

class HeldSaleController extends Controller
{
    public function __construct(private POSCartService $cart) {}

    public function held(Request $request)
    {
        return view('pos::sales.held', [
            'title' => __('pos::page_003.held_sales'),
            'sales' => $this->cart->heldSales($request),
        ]);
    }

    public function suspended(Request $request)
    {
        return view('pos::sales.suspended', [
            'title' => __('pos::page_003.suspended_sales'),
            'sales' => $this->cart->suspendedSales($request),
        ]);
    }
}
