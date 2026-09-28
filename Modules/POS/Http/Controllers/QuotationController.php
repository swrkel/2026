<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSCartService;

class QuotationController extends Controller
{
    public function __construct(private POSCartService $cart) {}

    public function index(Request $request)
    {
        return view('pos::sales.quotations', [
            'title' => __('pos::page_003.quotations'),
            'quotations' => $this->cart->quotations($request),
        ]);
    }

    public function store(Request $request)
    {
        return response()->json($this->cart->createQuotation($request, $request->input('note')));
    }
}
